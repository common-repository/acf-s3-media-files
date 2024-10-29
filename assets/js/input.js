(($) => {

    // remove unsafe characters
    // see http://docs.aws.amazon.com/AmazonS3/latest/dev/UsingMetadata.html#object-key-guidelines

    /**
     * @param {string} name
     * @returns {string}
     */
    function sanitize(name) {
        return ('' + name)

            // these might cause problems
            .replace('&', '')
            .replace('$', '')
            .replace('@', '')
            .replace('=', '')
            .replace(';', '')
            .replace(':', '')
            .replace('+', '')
            .replace(',', '')
            .replace('?', '')

            // these should be avoided
            .replace('\\', '')
            .replace('{', '')
            .replace('^', '')
            .replace('}', '')
            .replace('%', '')
            .replace('`', '')
            .replace(']', '')
            .replace('\'', '')
            .replace('"', '')
            .replace('>', '')
            .replace('[', '')
            .replace('~', '')
            .replace('<', '')
            .replace('#', '')
            .replace('|', '');
    }

    const config = Object.assign({
        /**
         * Base path for all queued files. Needs to end "/" if non-empty.
         * @param {jQuery} $elem
         * @returns {string}
         */
        getBaseKey($elem) {
            return '';
        },

        /**
         *
         * @param {jQuery} $elem
         * @param {File} file
         * @returns {string}
         */
        getKey($elem, file) {
            return sanitize(config.getBaseKey($elem) + file.name);
        },

        /**
         * Executed when a file is added to the queue.
         * Return false to prevent a file from being added to the queue.
         *
         * @param {jQuery} $elem
         * @param {File} file
         * @returns {boolean}
         */
        onFileAdd($elem, file) {
        },
    }, window.acfs3 || {});

    /**
     * @param {jQuery} $target
     * @param {Function} template
     * @param {object} data
     * @returns {jQuery}
     */
    function updateTemplate($target, template, data) {
        return $target.html(template(data));
    }

    /**
     * @param {string} key
     * @param {string} value
     * @param {number} postId
     * @returns {jQuery.Deferred}
     */
    function updateField(key, value, postId) {
        return $.ajax({
            method: 'post',
            url: ajaxurl + '?action=acf-s3_update_field',
            dataType: 'json',
            contentType: 'application/json; charset=UTF-8',
            processData: false,
            data: JSON.stringify({
                key: key,
                value: value,
                post_id: postId,
            }),
        });
    }

    /**
     * @param {jQuery} $el
     * @returns {void}
     */
    function updateBaseKey($el) {
        $el.find('.acf-s3-base-key').html(sanitize(config.getBaseKey($el)));
    }

    /**
     * @param {string} key
     * @param {number} postId
     * @param {string} baseKey
     * @return {jQuery.Deferred}
     */
    function relink(key, postId, baseKey) {
        return $.ajax({
            method: 'POST',
            url: ajaxurl + '?action=acf-s3_relink',
            dataType: 'json',
            contentType: 'application/json; charset=UTF-8',
            processData: false,
            data: JSON.stringify({
                key: key,
                post_id: postId,
                base_key: baseKey,
            }),
        });
    }

    /**
     * @param {jQuery} $el
     * @returns {void}
     */
    function initialize_field($el) {
        const $templateEl = $el.find('.acf-s3-files');
        const template = _.template($('#acf-s3-file-template').text());
        let files = $templateEl.data('files');
        const postId = parseInt($templateEl.data('post-id'), 10);
        const fieldKey = $el.data('key') || $el.data('field_key');

        if (!$.isArray(files)) {
            files = [];
        }

        const render = updateTemplate.bind(null, $templateEl, template);

        render({files: files});
        $el.on('update.basekey', updateBaseKey.bind(null, $el));
        $el.trigger('update.basekey');

        const proxy = new S3Proxy(ajaxurl);
        proxy.buildUrl = (action) => {
            return proxy.proxyUrl + '?action=acf-s3_media_files_action&command=' + action
        };

        const uploader = new S3FileUploader(proxy);

        // make sure all files are uploaded before we submit the form
        $('form[name=post]').on('submit', (event) => {

            const filesAreUploaded = files.every((it) => {
                return it.uploaded;
            });

            if (!filesAreUploaded && !confirm('Discard non-uploaded S3 media?')) {
                event.preventDefault();
                event.stopPropagation();

                return false;
            }

            // remove non-uploaded media
            files = files.filter((it) => {
                return it.uploaded;
            });

            render({files: files});
        });

        /*
        proxy.listMultipartUploads().done(function(result) {

            if ( !result.Uploads ) {
                return;
            }

            result.Uploads.forEach(function(u) {
                proxy.abortMultipartUpload(u.Key, u.UploadId);
            });
        });
        */

        $el.on('change', 'input[type=file]', (event) => {
            const $this = $(event.target);
            const file = $this[0].files[0];

            // remove the file from the "Add file" button
            $this.val(null);

            // run the onFileAdd callback
            if (false === config.onFileAdd($el, file)) {
                return;
            }

            files.push({
                name: config.getKey($el, file),
                uploaded: false,
                file: file
            });

            render({files: files});
        });

        $el.on('click', '.acf-s3-upload', (event) => {
            const $this = $(event.target);
            $this.html('Uploading...');
            $this.prop('disabled', true);
            const $file = $this.closest('.acf-s3-file');
            const id = parseInt($file.data('id'));
            const item = files[id];
            const file = item.file;

            if (file) {
                const name = config.getKey($el, file);

                $file.find('.progress').css('width', '1%');

                uploader.upload(name, file).then((res) => {
                    item.uploaded = true;
                    render({files: files});

                    // update the acf data in the db
                    updateField(fieldKey, _.pluck(files, 'name'), postId);
                }, null, (progress) => {
                    $file.find('.progress').css({
                        width: Math.round(100 * progress.position) + '%',
                    });
                });
            }
        });

        $el.on('click', '.acf-s3-delete', (event) => {
            event.preventDefault(); // this is a link without target, so disable it

            if (!confirm('Are you sure?')) {
                return;
            }

            const $this = $(event.target);

            $this.html('Deleting...');
            $this.prop('disabled', true);

            const id = parseInt($this.closest('.acf-s3-file').data('id'));
            const item = files[id];

            proxy.deleteObject(item.name).then((res) => {
                files.splice(id, 1);
                render({files: files});

                updateField(fieldKey, _.pluck(files, 'name'), postId);
            });
        });

        $el.on('click', '.acf-s3-relink', (event) => {
            event.preventDefault();

            const $this = $(event.target);
            $this.prop('disabled', true);

            const bk = sanitize(config.getBaseKey($el));

            relink(fieldKey, postId, bk).then((res) => {
                const tmpFiles = res.map((f) => {
                    return {name: f, uploaded: true};
                });
                files = tmpFiles;
                render({files: files});
                $this.prop('disabled', false);
            });
        });
    }


    if (typeof acf.add_action !== 'undefined') {

        /*
        *  ready append (ACF5)
        *
        *  These are 2 events which are fired during the page load
        *  ready = on page load similar to $(document).ready()
        *  append = on new DOM elements appended via repeater field
        *
        *  @type	event
        *  @date	20/07/13
        *
        *  @param	$el (jQuery selection) the jQuery element which contains the ACF fields
        *  @return	n/a
        */

        acf.add_action('ready append', ($el) => {
            // search $el for fields of type 'FIELD_NAME'
            acf.get_fields({type: 's3_media_files'}, $el).each(function() {
                initialize_field($(this));
            });
        });
    } else {
        /*
        *  acf/setup_fields (ACF4)
        *
        *  This event is triggered when ACF adds any new elements to the DOM.
        *
        *  @type	function
        *  @since	1.0.0
        *  @date	01/01/12
        *
        *  @param	event		e: an event object. This can be ignored
        *  @param	Element		postbox: An element which contains the new HTML
        *
        *  @return	n/a
        */
        $(document).on('acf/setup_fields', function (e, postbox) {
            $(postbox).find('.field[data-field_type="s3_media_files"]').each(function () {
                initialize_field($(this));
            });
        });
    }
})(jQuery);

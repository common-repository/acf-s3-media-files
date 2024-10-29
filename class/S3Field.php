<?php
declare(strict_types=1);

namespace Codemenschen\AcfS3;

use \acf_field;

class S3Field extends acf_field
{
    /**
     * @var string
     */
    private $bucket;

    /**
     * @var array
     */
    private $settings;

    /**
     * acf_field_s3_media_files_v5 constructor.
     * @param string $bucket
     */
    public function __construct(string $bucket)
    {
        $this->bucket = $bucket;
        $this->name = 's3_media_files';
        $this->label = __('S3 Media Files', 'acf_field_s3_media_files');
        $this->category = 'content';
        $this->settings = [
            'version' => '1.0.0',
        ];
        parent::__construct();

    }

    /**
     * @param array $field
     * @return void
     */
    public function render_field_settings($field)
    {
    }

    /**
     * @param array $field
     * @return void
     */
    public function render_field($field)
    {
        // create Field HTML
        $values = is_array($field['value']) ? $field['value'] : [];

        $files = array_map(function ($name) {
            return ['name' => $name, 'uploaded' => true];
        }, $values);

        // reset array keys
        $files = array_values($files); 
        ?>
            <!-- <div class="clearfix">
                <div style="float: left;">
                    Base key: <span class="acf-s3-base-key"></span>
                </div>
                <div style="float:right;">
                    <button class="button acf-s3-relink">Relink</button>
                </div>
            </div> -->

            <br/> 

            <div class="acf-s3-files"
                data-post-id="<?php echo get_the_ID(); ?>"
                data-files="<?php echo htmlspecialchars(json_encode($files), ENT_QUOTES); ?>">

            </div>

            <br/>

            <input type="file" id="acf-s3-file-select"/>
        <?php
    }

    /**
     * @return void
     */
    public function input_admin_enqueue_scripts()
    {
        $baseUrl = plugin_dir_url(__DIR__);
        wp_register_script('promise-queue', $baseUrl . 'assets/js/PromiseQueue.js', ['jquery'], $this->settings['version']);
        wp_register_script('s3-proxy', $baseUrl . 'assets/js/S3Proxy.js', ['jquery'], $this->settings['version']);
        wp_register_script('s3-file-uploader', $baseUrl . 'assets/js/S3FileUploader.js', ['jquery', 's3-proxy', 'promise-queue'], $this->settings['version']);
        wp_register_script('acf-s3_media_files', $baseUrl . 'assets/js/input.js', ['acf-input', 's3-file-uploader', 'underscore'], $this->settings['version'], true);
        wp_register_style('acf-s3_media_files', $baseUrl . 'assets/css/input.css');

        wp_enqueue_script('acf-s3_media_files');
        wp_enqueue_style('acf-s3_media_files');
    }

    /**
     * @return void
     */
    public function input_admin_head()
    {
        // Note: This function can be removed if not used
        ?>

        <script type="text/template" id="acf-s3-file-template">

            <% for (var i = 0; i < files.length; i++) { %>

            <div class="acf-s3-file" data-id="<%= i %>">

                <div class="progress<% if (files[i].uploaded) { %> done<% } %>"></div>

                <div class="content">

                    <div class="name">

                        <% if (files[i].uploaded) { %>

                        <a href="https://<?php echo $this->bucket; ?>.s3.amazonaws.com/<%= encodeURI(files[i].name) %>"
                           target="_blank">
                            <%= files[i].name %>
                        </a>

                        <% } else { %>

                        <%= files[i].name %>

                        <% } %>

                    </div>

                    <div class="actions">
                        <% if ( !files[i].uploaded ) { %>
                        <a class="acf-s3-upload" style="float: right;">Upload</a>
                        <% } %>

                        <a class="acf-s3-delete" style="float: right;">Delete</a>
                    </div>

                    <div class="clear"></div>

                </div>
            </div>

            <% } %>
        </script>

        <?php
    }

    /**
     * @param mixed $value
     * @param int $post_id
     * @param array $field
     * @return mixed
     */
    public function load_value($value, $post_id, $field)
    {
        if (is_ssl()) {
            $link = 'https';
        } else {
            $link = 'http';
        }
        if(!is_admin()) {
            $value = sprintf('%s://%s.s3.amazonaws.com/%s',$link, get_option('acf_s3_bucket'), $value[0]);
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @param int $post_id
     * @param array $field
     * @return mixed
     */
    public function update_value($value, $post_id, $field)
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @param int $post_id
     * @param array $field
     * @return mixed
     */
    public function format_value($value, $post_id, $field)
    {
        return $value;
    }

    /**
     * @param array $field
     * @return array
     */
    public function load_field($field)
    {
        return $field;
    }

    /**
     * @param array $field
     * @return array
     */
    public function update_field($field)
    {
        return $field;
    }
}

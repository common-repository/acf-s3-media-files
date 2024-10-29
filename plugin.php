<?php
/*
Plugin Name: ACF S3 Media Files
Description: Adds a new field type that allows media to be uploaded to AWS S3
Version: 1.1.2
Author: Codemenschen
Author URI: https://www.codemenschen.at/
*/
require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Codemenschen\AcfS3\S3Proxy;
use Codemenschen\AcfS3\S3Item;
use Codemenschen\AcfS3\S3Field;

load_plugin_textdomain('acf-s3_media_files', false, dirname(plugin_basename(__FILE__)) . '/lang/');

add_action( 'admin_menu', 'acf_s3_options_page');
function acf_s3_options_page() {

    // add top level menu page
    add_menu_page(
        'ACF S3 Config',
        'ACF S3 Config',
        'manage_options',
        'acf_s3_page',
        'acf_s3_option_page'
    );

    function acf_s3_option_page() {
        // check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
        return;
   }
   ?>

    <div class="wrap acf_s3">
        <div class="s3-container">
            <div class="s3-banner">
                <a href="https://www.codemenschen.at/plugins/acf-s3-media-files/" target="_blank"><img src="<?php echo plugins_url( '/assets/images/banner-acf-2.jpg', __FILE__ ); ?>" style="width: 100%;"></a>
            </div>
            <div class="s3-content column-9 s3-widget-support">
                <?php 
                if(strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && $_POST['submit_key'] == 'Submit') {
                    $acf_s3_key = sanitize_text_field($_POST['acf_s3_key']);
                    if (!empty($acf_s3_key)) {
                        update_option( 'acf_s3_key', $acf_s3_key );
                    }
                    $acf_s3_secret = sanitize_text_field($_POST['acf_s3_secret']);
                    if (!empty($acf_s3_secret)) {
                        update_option( 'acf_s3_secret', $acf_s3_secret );
                    }
                    $acf_s3_bucket = sanitize_text_field($_POST['acf_s3_bucket']);
                    if (!empty($acf_s3_bucket)) {
                        update_option( 'acf_s3_bucket', $acf_s3_bucket );
                    }
                    $acf_s3_region = sanitize_text_field($_POST['acf_s3_region']);
                    if (!empty($acf_s3_region)) {
                        update_option( 'acf_s3_region', $acf_s3_region );
                    }
                    try {	
                        $config_check = array(
                            'acf_s3_region' => get_option('acf_s3_region'),
                            'acf_s3_bucket' => get_option('acf_s3_bucket'),
                            'acf_s3_key' => get_option('acf_s3_key'),
                            'acf_s3_secret' => get_option('acf_s3_secret'),
                        );
                        $client = acf_s3_get_client($config_check);
                        try {
                            $client->GetBucketLocation([
                                'Bucket' => get_option('acf_s3_bucket')
                            ]);
                            $msg_key = 'Update successful...';

                        } catch (S3Exception $e) {
                            $msg_key = 'Plz check again config s3, configuration incorrect...';
                        }
                    } catch (Aws\S3\Exception\S3Exception $e) {
                        $msg_key = 'Plz check again field config s3, configuration incorrect...';
                    } 
                }
                ?>
                <h2>ACF S3 Config</h2>
                <?php 
                if (isset($msg_key)) {
                   echo '<p class="msg">'.$msg_key.'</p>';
                } ?>
                <form method="post" accept-charset="utf-8">
                    <div class="group-btn">
                        <label for="acf_s3_key">Key:</label>
                        <input type="text" id="acf_s3_key" name="acf_s3_key" value="<?php echo esc_attr(get_option( 'acf_s3_key' )); ?>">
                        <a href="https://www.codemenschen.at/docs/acf-s3-media-files-documentation/aws-s3-guideline/creating-access-key-id-and-secret-key/" target="_blank" class="link-s3">
                            <i aria-hidden="true" class="dashicons dashicons-external"></i>Create an Access Key ID
                        </a>
                    </div>
                    <div class="group-btn">
                        <label for="acf_s3_secret">Secret:</label>
                        <input type="text" id="acf_s3_secret" name="acf_s3_secret" value="<?php echo esc_attr(get_option( 'acf_s3_secret' )); ?>">
                        <a href="https://www.codemenschen.at/docs/acf-s3-media-files-documentation/aws-s3-guideline/creating-access-key-id-and-secret-key/" target="_blank" class="link-s3">
                            <i aria-hidden="true" class="dashicons dashicons-external"></i>Create an Secret key
                        </a>
                    </div>
                    <div class="group-btn">
                        <label for="acf_s3_bucket">Bucket:</label>
                        <input type="text" id="acf_s3_bucket" name="acf_s3_bucket" value="<?php echo esc_attr(get_option( 'acf_s3_bucket' )); ?>">
                        <a href="https://www.codemenschen.at/docs/acf-s3-media-files-documentation/aws-s3-guideline/get-started-with-creating-buckets/" target="_blank" class="link-s3">
                            <i aria-hidden="true" class="dashicons dashicons-external"></i>Create an Buckets
                        </a>
                    </div>
                    <div class="group-btn">
                        <label for="acf_s3_region">Region:</label>
                        <?php 
                        $list_region = [
                            'us-east-2' => 'US East (Ohio)',
                            'us-east-1' => 'US East (N. Virginia)',
                            'us-west-1' => 'US West (N. California)',
                            'us-west-2' => 'US West (Oregon)',
                            'af-south-1' => 'Africa (Cape Town)',
                            'ap-east-1' => 'Asia Pacific (Hong Kong)',
                            'ap-south-1' => 'Asia Pacific (Mumbai)',
                            'ap-northeast-3' => 'Asia Pacific (Osaka)',
                            'ap-northeast-2' => 'Asia Pacific (Seoul)',
                            'ap-southeast-1' => 'Asia Pacific (Singapore)',
                            'ap-southeast-2' => 'Asia Pacific (Sydney)',
                            'ap-northeast-1' => 'Asia Pacific (Tokyo)',
                            'ca-central-1' => 'Canada (Central)',
                            'cn-north-1' => 'China (Beijing)',
                            'cn-northwest-1' => 'China (Ningxia)',
                            'eu-central-1' => 'Europe (Frankfurt)',
                            'eu-west-1' => 'Europe (Ireland)',
                            'eu-west-2' => 'Europe (London)',
                            'eu-south-1' => 'Europe (Milan)',
                            'eu-west-3' => 'Europe (Paris)',
                            'eu-north-1' => 'Europe (Stockholm)',
                            'me-south-1' => 'Middle East (Bahrain)',
                            'sa-east-1' => 'South America (São Paulo)',
                        ];
                        ?>
                        <select name="acf_s3_region" id="acf_s3_region">
                            <?php 
                            foreach($list_region as $region => $name_region) {
                                if(esc_attr(get_option( 'acf_s3_region' )) == $region) {
                                    $class = "selected";
                                } else {
                                    $class = "";
                                }
                                echo '<option '.$class.' value="'.$region.'">'.$name_region.'</option>';
                            } 
                            ?>
                        </select>
                        <a href="https://www.codemenschen.at/docs/acf-s3-media-files-documentation/aws-s3-guideline/setting-the-aws-region/" target="_blank" class="link-s3">
                            <i aria-hidden="true" class="dashicons dashicons-external"></i>Setting the AWS Region
                        </a>
                    </div>
                    <input type="submit" class="button" id="submit_key" name="submit_key" value="Submit">
                </form>
            </div>
            <div class="s3-sidebar column-3 s3-widget-support">
                <div class="s3-title-box">
                    <h2>Are you already a customer?</h2>
                </div>
                <p>Let's connect! We woud love to help you </p>
                <ul>
                    <li class="email-icon">Email: <a href="mailto:gdpr@codemenschen.at" target="_blank">gdpr@codemenschen.at</a></li>
                    <li class="skype-icon">Skype: live:gdpr_22</li>
                    <li class="chat-icon">Live Chat: <a href="https://www.codemenschen.at" target="_blank">codemenschen.at</a></li>
                    <li class="ticket-icon">Support Ticket: <a href="https://www.codemenschen.at/submit-ticket/?section=create-ticket" target="_blank">codemenschen.at</a></li>
                </ul>
            </div>
            <div class="s3-documentation column-3 s3-widget-support">
                <div class="s3-wrap-box">
                    <div class="s3-title-box">
                        <h2>Rate Our Plugin</h2>
                    </div>
                    <div class="s3-widget-content">
                        <div class="star-ratings">
                            <span class="dashicons dashicons-star-filled"></span>
                            <span class="dashicons dashicons-star-filled"></span>
                            <span class="dashicons dashicons-star-filled"></span>
                            <span class="dashicons dashicons-star-filled"></span>
                            <span class="dashicons dashicons-star-filled"></span>
                        </div>
                        <p>Did ACF S3 Media Files help you out? Please leave a 5-star review. Thank you!</p>
                        <a href="https://wordpress.org/support/plugin/acf-s3-media-files/reviews/#new-post" target="_blank" class="button">Write a review</a>
                    </div>                    
                </div>
                <div class="s3-wrap-box">
                    <div class="s3-title-box">
                        <h2>Customization Service</h2>
                    </div>
                    <p>We are a European Company. To hire our agency to help you with this plugin installation or any other customization or requirements please contact us through our site <a href="https://www.codemenschen.at/contact/" target="_blank">contact form</a> or email <a href="mailto:gdpr@codemenschen.at" target="_blank">gdpr@codemenschen.at</a> directly.</p>
                    <a href="https://www.codemenschen.at/contact/" class="button" target="_blank">Hire Us Now</a>
                </div>
            </div>
            <div class="s3-documentation column-3 s3-widget-support">
                <div class="s3-wrap-box">
                    <div class="s3-title-box">
                        <h2>ACF S3 Media Files Documentation</h2>
                    </div>
                    <ul class="list-doc">
                        <li><a href="https://www.codemenschen.at/docs/acf-s3-media-files-documentation/download-the-free-version/" target="_blank"><i aria-hidden="true" class="dashicons dashicons-external"></i>Download the free version</a></li>
                        <li><a href="https://www.codemenschen.at/docs/acf-s3-media-files-documentation/installation-instructions/ "target="_blank"><i aria-hidden="true" class="dashicons dashicons-external"></i>Installation Instructions</a></li>
                        <li><a href="https://www.codemenschen.at/docs/acf-s3-media-files-documentation/plugin-settings/" target="_blank"><i aria-hidden="true" class="dashicons dashicons-external"></i>Plugin Settings</a></li>
                        <li><a href="https://www.codemenschen.at/docs/acf-s3-media-files-documentation/compatibility/" target="_blank"><i aria-hidden="true" class="dashicons dashicons-external"></i>Compatibility</a></li>
                        <li><a href="https://www.codemenschen.at/docs/acf-s3-media-files-documentation/changelog-details/" target="_blank"><i aria-hidden="true" class="dashicons dashicons-external"></i>Changelog</a></li>
                    </ul>                
                </div>
            </div>            
            <div class="s3-documentation column-3 s3-widget-support">
                <div class="s3-wrap-box">
                    <div class="s3-title-box">
                        <h2>Video guide</h2>
                    </div>
                    <div class="video">
                        <video width="100%" height="240" controls>
                           <source src="https://www.codemenschen.at/wp-content/uploads/2021/02/acf_s3_media_files.mp4" type="video/mp4">
                        </video>
                    </div>
                    <a href="https://www.youtube.com/watch?reload=9&v=BLTy2tQXQLY" target="_blank" style="margin-top: 15px;" class="link-s3">
                        <i aria-hidden="true" class="dashicons dashicons-external"></i>Video guide for AWS S3
                    </a>                  
                </div>
            </div>
        </div>
    </div>
   <?php }
}

add_action('admin_enqueue_scripts', 'acf_s3_form_admin_style_init' );
function acf_s3_form_admin_style_init() {
    wp_enqueue_style('acf-s3-styles', plugin_dir_url( __FILE__ ) .'/assets/css/acf-s3-style.css', array(), null);
}

const ACF_S3_OPTIONS = [
    'acf_s3_region' => '',
    'acf_s3_bucket' => '',
    'acf_s3_key' => '',
    'acf_s3_secret' => '',
];

/**
 * @param string[] $config
 * @return S3Client
 */
function acf_s3_get_client(array $config)
{
    return new S3Client([
        'credentials' => [
            'key' => $config['acf_s3_key'],
            'secret' => $config['acf_s3_secret'],
        ],
        'region' => $config['acf_s3_region'],
        'version' => 'latest',
    ]);
}

/**
 * @return string[]
 */
function acf_s3_get_config(): array
{
    
    /* @var array|null $config */
    static $config = null;
    if ($config === null) {
        $config = [];
        foreach (ACF_S3_OPTIONS as $key => $name) {
            // if(empty(get_option($key))) {
            //     update_option( $key, $name);
            // }
            $config[$key] = get_option($key);
        }
    }
    return $config;
}

/**
 * @param string $fieldKey
 * @param int|false $postId defaulted to false to preserve backwards compatibility with ACF get_field()
 * @return S3Item[]
 */
function acf_s3_get_field(string $fieldKey, $postId = false)
{
    $names = get_field($fieldKey, $postId, false);
    $conf = acf_s3_get_config();

    if (!is_array($names)) {
        $names = [];
    }

    return array_map(function ($n) use ($conf) {
        return new S3Item($conf['acf_s3_bucket'], $n);
    }, $names);
}

/**
 * Scans a location in S3 and updates the linked files in a post
 *
 * @param string $fieldKey acf field key
 * @param int $postId post id to link to
 * @param string $baseKey base key to scan in s3
 * @return string[] keys to the linked files
 */
function acf_s3_relink(string $fieldKey, int $postId, string $baseKey): array
{
    $config = acf_s3_get_config();
    $s3 = acf_s3_get_client($config);

    // make sure the key only ends with a slash if we're not at the root
    $baseKey = ltrim(trim($baseKey, '/') . '/', '/');
    $data = $s3->listObjects([
        'Bucket' => $config['acf_s3_bucket'],
        'Prefix' => $baseKey,
    ])->toArray();

    $contents = isset($data['Contents']) ? $data['Contents'] : [];

    // if directories have been created manually on S3, empty "ghost files" will
    // appear with the same key as the base key. Remove them.
    $contents = array_filter($contents, function ($it) use ($baseKey) {
        return $it['Key'] !== $baseKey;
    });

    // if elements have been removed by the filter there might be holes in the array.
    // this can cause json_encode to return an object instead of an array.
    $contents = array_values($contents);

    $items = array_map(function ($it) {
        return $it['Key'];
    }, $contents);
   
    $check = update_field($fieldKey, $items, $postId);
    return $items;
}

/**
 * @return mixed
 */
function acf_s3_getJsonBody()
{
    $data = file_get_contents('php://input');
    return json_decode($data, true);
}

// v5
add_action('acf/include_fields', function () {
    $config = acf_s3_get_config();
    new S3Field($config['acf_s3_bucket']);
});

add_action('wp_ajax_acf-s3_media_files_action', function () {
    $config = acf_s3_get_config();
    $client = acf_s3_get_client($config);
    $action = isset($_GET['command']) ? sanitize_text_field($_GET['command']) : '';
    $proxy = new S3Proxy($client, $config['acf_s3_bucket']);
    // var_dump($proxy);
    // var_dump($client);exit;
    $body = acf_s3_getJsonBody();
    $out = [];
    switch ($action) {
        case 'createMultipartUpload':
            $out = $proxy->createMultipartUpload($body['Key'], $body['ContentType']);
            break;
        case 'abortMultipartUpload':
            $out = $proxy->abortMultipartUpload($body['Key'], $body['UploadId']);
            break;
        case 'completeMultipartUpload':
            $out = $proxy->completeMultipartUpload($body['Key'], $body['Parts'], $body['UploadId']);
            break;
        case 'listMultipartUploads':
            $out = $proxy->listMultipartUploads();
            break;
        case 'signUploadPart':
            $out = $proxy->signUploadPart($body['Key'], $body['PartNumber'], $body['UploadId']);
            break;
        case 'deleteObject':
            $out = $proxy->deleteObject($body['Key']);
            break;
        default:
            throw new Exception('No matching action found');
    }

    echo json_encode($out);

    die();
});

add_action('wp_ajax_acf-s3_update_field', function () {
    $body = acf_s3_getJsonBody();
    $key = sanitize_text_field($body['key']);
    $value = $body['value'];
    $postId = sanitize_text_field($body['post_id']);
    update_field($key, $value, $postId);
    die();
});

add_action('wp_ajax_acf-s3_relink', function () {
    $body = acf_s3_getJsonBody();
    $key = sanitize_text_field($body['key']);
    $postId = sanitize_text_field($body['post_id']);
    $path = sanitize_text_field($body['base_key']);

    $items = acf_s3_relink($key, $postId, $path);

    echo json_encode($items);

    die();
});

/**
 * @param array $args
 * @return void
 */
function acf_s3_create_input(array $args): void
{
    echo '<input class="regular-text" type="'.esc_attr__($args['type']).'" id="'.esc_attr__($args['name']).'" name="'.esc_attr__($args['name']).'" value="'.esc_attr__($args['value']).'" />';
}

add_action('admin_init', function () {
    $group = 'acf_s3_media_files';
    $fields = ACF_S3_OPTIONS;

    // remove api token when deactivating plugin
    register_deactivation_hook(__FILE__, function () use ($fields) {
        foreach ($fields as $key => $name) {
            delete_option($key);
        }
    });

    add_settings_section($group, 'ACF S3 Content', '', 'general');

    foreach ($fields as $key => $name) {
        add_settings_field($key, $name, 'acf_s3_create_input', 'general', $group, [
            'name' => $key,
            'value' => get_option($key)
        ]);
        register_setting('general', $key);
    }
});

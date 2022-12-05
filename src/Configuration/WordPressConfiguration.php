<?php

declare(strict_types=1);

/*
 * This file is part of Ymir WordPress plugin.
 *
 * (c) Carl Alexander <support@ymirapp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ymir\Plugin\Configuration;

use Ymir\Plugin\DependencyInjection\Container;
use Ymir\Plugin\DependencyInjection\ContainerConfigurationInterface;

/**
 * Configures the dependency injection container with WordPress parameters and services.
 */
class WordPressConfiguration implements ContainerConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function modify(Container $container)
    {
        $container['blog_charset'] = $container->service(function () {
            return get_bloginfo('charset');
        });
        $container['content_directory'] = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : '';
        $container['content_directory_name'] = defined('CONTENT_DIR') ? CONTENT_DIR : 'wp-content';
        $container['content_url'] = defined('WP_CONTENT_URL') ? WP_CONTENT_URL : '';
        $container['content_width'] = $container->service(function () {
            return isset($GLOBALS['content_width']) && is_numeric($GLOBALS['content_width']) ? (int) $GLOBALS['content_width'] : null;
        });
        $container['current_user'] = $container->service(function () {
            return wp_get_current_user();
        });
        $container['default_email_from'] = $container->service(function () {
            $sitename = strtolower(wp_parse_url(network_home_url(), PHP_URL_HOST));

            if ('www.' === substr($sitename, 0, 4)) {
                $sitename = substr($sitename, 4);
            }

            return 'wordpress@'.$sitename;
        });
        $container['filesystem'] = $container->service(function () {
            if (!class_exists(\WP_Filesystem_Direct::class)) {
                require_once ABSPATH.'wp-admin/includes/class-wp-filesystem-base.php';
                require_once ABSPATH.'wp-admin/includes/class-wp-filesystem-direct.php';
            }

            return new \WP_Filesystem_Direct(false);
        });
        $container['base_image_sizes'] = $container->service(function () {
            $sizes = [
                'thumb' => [
                    'width' => (int) get_option('thumbnail_size_w'),
                    'height' => (int) get_option('thumbnail_size_h'),
                    'crop' => (bool) get_option('thumbnail_crop'),
                ],
                'medium' => [
                    'width' => (int) get_option('medium_size_w'),
                    'height' => (int) get_option('medium_size_h'),
                    'crop' => false,
                ],
                'medium_large' => [
                    'width' => (int) get_option('medium_large_size_w'),
                    'height' => (int) get_option('medium_large_size_h'),
                    'crop' => false,
                ],
                'large' => [
                    'width' => (int) get_option('large_size_w'),
                    'height' => (int) get_option('large_size_h'),
                    'crop' => false,
                ],
                'full' => [
                    'width' => null,
                    'height' => null,
                    'crop' => false,
                ],
            ];

            // Compatibility mapping as found in wp-includes/media.php.
            $sizes['thumbnail'] = $sizes['thumb'];

            return $sizes;
        });
        $container['is_multisite'] = is_multisite();
        $container['phpmailer'] = function () {
            if (!class_exists(\PHPMailer::class)) {
                require_once ABSPATH.WPINC.'/class-phpmailer.php';
            }

            return new \PHPMailer(true);
        };
        $container['plupload_error_messages'] = $container->service(function () {
            return [
                'queue_limit_exceeded' => __('You have attempted to queue too many files.'),
                'file_exceeds_size_limit' => __('%s exceeds the maximum upload size for this site.'),
                'zero_byte_file' => __('This file is empty. Please try another.'),
                'invalid_filetype' => __('Sorry, this file type is not permitted for security reasons.'),
                'not_an_image' => __('This file is not an image. Please try another.'),
                'image_memory_exceeded' => __('Memory exceeded. Please try another smaller file.'),
                'image_dimensions_exceeded' => __('This is larger than the maximum size. Please try another.'),
                'default_error' => __('An error occurred in the upload. Please try again later.'),
                'missing_upload_url' => __('There was a configuration error. Please contact the server administrator.'),
                'upload_limit_exceeded' => __('You may only upload 1 file.'),
                'http_error' => __('Unexpected response from the server. The file may have been uploaded successfully. Check in the Media Library or reload the page.'),
                'http_error_image' => __('Post-processing of the image failed. If this is a photo or a large image, please scale it down to 2500 pixels and upload it again.'),
                'upload_failed' => __('Upload failed.'),
                'big_upload_failed' => __('Please try uploading this file with the %1$sbrowser uploader%2$s.'),
                'big_upload_queued' => __('%s exceeds the maximum upload size for the multi-file uploader when used in your browser.'),
                'io_error' => __('IO error.'),
                'security_error' => __('Security error.'),
                'file_cancelled' => __('File canceled.'),
                'upload_stopped' => __('Upload stopped.'),
                'dismiss' => __('Dismiss'),
                'crunching' => __('Crunching&hellip;'),
                'deleted' => __('moved to the trash.'),
                'error_uploading' => __('&#8220;%s&#8221; has failed to upload.'),
            ];
        });
        $container['rest_url'] = $container->service(function () {
            global $wp_rewrite;

            if (!$wp_rewrite instanceof \WP_Rewrite) {
                $wp_rewrite = new \WP_Rewrite();
                $wp_rewrite->init();
            }

            return get_rest_url();
        });
        $container['site_icon'] = $container->service(function () {
            if (!class_exists(\WP_Site_Icon::class)) {
                require_once ABSPATH.'wp-admin/includes/class-wp-site-icon.php';
            }

            return new \WP_Site_Icon();
        });
        $container['site_query'] = $container->service(function () {
            return class_exists(\WP_Site_Query::class) ? new \WP_Site_Query() : null;
        });
        $container['site_url'] = $container->service(function () {
            return set_url_scheme(get_home_url(), 'https');
        });
        $container['uploads_basedir'] = $container->service(function () {
            return wp_upload_dir()['basedir'] ?? '';
        });
        $container['uploads_baseurl'] = $container->service(function () {
            return wp_upload_dir()['baseurl'] ?? '';
        });
        $container['uploads_path'] = $container->service(function () {
            return wp_upload_dir()['path'] ?? '';
        });
        $container['uploads_subdir'] = $container->service(function () {
            return wp_upload_dir()['subdir'] ?? '';
        });
        $container['wp_object_cache'] = $container->service(function () {
            global $wp_object_cache;

            return $wp_object_cache;
        });
    }
}

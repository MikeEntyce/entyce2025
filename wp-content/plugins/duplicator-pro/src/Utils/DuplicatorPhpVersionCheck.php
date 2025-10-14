<?php

/**
 * These functions are performed before including any other Duplicator file so
 * do not use any Duplicator library or feature and use code compatible with PHP 5.2
 */

defined('ABSPATH') || exit;
// In the future it will be included on both PRO and LITE so you need to check if the define exists.
if (!class_exists('DuplicatorPhpVersionCheck')) {

    /**
     * PHP Version
     */
    class DuplicatorPhpVersionCheck // phpcs:ignore 
    {
        /** @var string */
        protected static $minVer = '';
        /** @var string */
        protected static $suggestedVer = '';

        /**
         * Check PHP version
         *
         * @param string $minVer       minumum version
         * @param string $suggestedVer suggested version
         *
         * @return bool
         */
        public static function check($minVer, $suggestedVer)
        {
            self::$minVer       = $minVer;
            self::$suggestedVer = $suggestedVer;

            if (version_compare(PHP_VERSION, self::$minVer, '<')) {
                if (is_multisite()) {
                    add_action('network_admin_notices', array(__CLASS__, 'notice'));
                } else {
                    add_action('admin_notices', array(__CLASS__, 'notice'));
                }
                return false;
            } else {
                return true;
            }
        }

        /**
         * Display notice
         *
         * @return void
         */
        public static function notice()
        {
            ?>
            <div class="error notice">
                <p>
                    <?php
                    printf(
                        esc_html__(
                            'DUPLICATOR PRO: Your system is running a very old version of PHP (%s) that is no longer supported by Duplicator.',
                            'duplicator-pro'
                        ),
                        PHP_VERSION
                    );
                    ?><br><br>
                    <b>
                    <?php
                    printf(
                        esc_html__(
                            'Please ask your host or server administrator to update to PHP %1s or greater.',
                            'duplicator-pro'
                        ),
                        esc_html(self::$suggestedVer)
                    );
                    ?></b><br>
                    <?php
                    printf(
                        esc_html__(
                            'If this is not possible, open a %1$shelp ticket%2$s and request a previous version of Duplicator Pro compatible with PHP %3$s.',
                            'duplicator-pro'
                        ),
                        '<a href="' . esc_url('https://duplicator.com/my-account/support') . '" target="blank">',
                        '</a>',
                        esc_html(self::$minVer)
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }

}

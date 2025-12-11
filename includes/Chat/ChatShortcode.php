<?php
/**
 * Placeholder chat shortcode class to prevent fatal errors after chat removal.
 */

declare(strict_types=1);

namespace JuntaPlay\Chat;

if (!class_exists(__NAMESPACE__ . '\\ChatShortcode')) {
    class ChatShortcode
    {
        /**
         * No-op constructor; chat functionality has been removed.
         */
        public function __construct()
        {
        }

        /**
         * Legacy init handler retained for compatibility with existing hooks.
         */
        public function init(): void
        {
        }

        /**
         * Some call sites may use register() instead of init(); keep both.
         */
        public function register(): void
        {
        }

        /**
         * Render method stub in case templates call the shortcode directly.
         */
        public function render(): string
        {
            return '';
        }
    }
}

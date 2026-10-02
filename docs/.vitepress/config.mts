import { defineConfig } from 'vitepress';

export default defineConfig({
  title: 'Klaviyo Connect',
  description: 'Sends Craft Commerce carts, orders and customers to Klaviyo, so you can run email and SMS flows from what shoppers do.',
  lang: 'en-US',
  cleanUrls: true,
  srcExclude: ['node_modules/**'],
  markdown: {
    // Leave Twig in inline code alone, since Vue would render {{ }} in it as a template
    config: (markdown) => {
      const renderCodeInline = markdown.renderer.rules.code_inline!;
      markdown.renderer.rules.code_inline = (...args) => renderCodeInline(...args).replace('<code', '<code v-pre');
    },
  },
  themeConfig: {
    logo: '/assets/img/icon.svg',
    search: {
      provider: 'local',
    },
    socialLinks: [{ icon: 'github', link: 'https://github.com/FosterCommerce/klaviyoconnect' }],
    sidebar: [
      {
        text: 'Getting started',
        items: [
          { text: 'Getting started', link: '/getting-started' },
          { text: 'Upgrading', link: '/upgrade' },
        ],
      },
      {
        text: 'User guide',
        items: [
          { text: 'Automatic tracking', link: '/user-guide/automatic-tracking' },
          { text: 'Tracking script', link: '/user-guide/tracking-script' },
          { text: 'List fields', link: '/user-guide/list-fields' },
          { text: 'Past orders', link: '/user-guide/past-orders' },
        ],
      },
      {
        text: 'Dev guide',
        items: [
          { text: 'Template examples', link: '/dev-guide/template-examples' },
          { text: 'PHP events', link: '/dev-guide/php-events' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'Configuration', link: '/reference/configuration' },
          { text: 'Event properties', link: '/reference/event-properties' },
          { text: 'Actions', link: '/reference/actions' },
          { text: 'Profile attributes', link: '/reference/profile-attributes' },
        ],
      },
      {
        text: 'Recipes',
        items: [
          { text: 'Send SMS', link: '/recipes/send-sms' },
          { text: 'Back in stock', link: '/recipes/back-in-stock' },
          { text: 'Product catalog', link: '/recipes/product-catalog' },
        ],
      },
    ],
  },
});

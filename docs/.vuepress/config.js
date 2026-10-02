module.exports = {
  // Leave Twig in inline code alone, since Vue would render {{ }} in it as a template
  extendMarkdown: (markdown) => {
    const renderCodeInline = markdown.renderer.rules.code_inline;
    markdown.renderer.rules.code_inline = (...args) => renderCodeInline(...args).replace('<code', '<code v-pre');
  },
  title: 'Klaviyo Connect',
  description: 'Sends Craft Commerce carts, orders and customers to Klaviyo, so you can run email and SMS flows from what shoppers do.',
  themeConfig: {
    logo: '/assets/img/icon.svg',
    repo: 'FosterCommerce/klaviyoconnect',
    displayAllHeaders: true,
    sidebar: [
      {
        title: 'Getting started',
        collapsable: false,
        children: ['/getting-started', '/upgrade'],
      },
      {
        title: 'User guide',
        collapsable: false,
        children: ['/user-guide/automatic-tracking', '/user-guide/tracking-script', '/user-guide/list-fields', '/user-guide/past-orders'],
      },
      {
        title: 'Dev guide',
        collapsable: false,
        children: ['/dev-guide/template-examples', '/dev-guide/php-events'],
      },
      {
        title: 'Reference',
        collapsable: false,
        children: ['/reference/configuration', '/reference/event-properties', '/reference/actions', '/reference/profile-attributes'],
      },
      {
        title: 'Recipes',
        collapsable: false,
        children: ['/recipes/send-sms', '/recipes/back-in-stock', '/recipes/product-catalog'],
      },
    ],
  }
}

# Flouci Payment Gateway for WooCommerce

![WooCommerce](https://img.shields.io/badge/WooCommerce-6.0%2B-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-4.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)

A secure payment gateway plugin for WooCommerce that integrates with Flouci's payment processing system, enabling Tunisian businesses to accept Credit Cards, E-Dinar, and Flouci Wallet payments.

## Features

- **Multiple Payment Methods**: Accept Credit Cards, E-Dinar, and Flouci Wallet payments
- **Secure Transactions**: All payments processed through Flouci's secure infrastructure
- **Mobile-Friendly**: Optimized checkout experience for mobile users
- **HPOS Compatible**: Fully compatible with WooCommerce's High-Performance Order Storage
- **Easy Setup**: Simple configuration with App Token and App Secret
- **Detailed Transaction Tracking**: Developer tracking ID for order reconciliation
- **Success/Fail Redirects**: Customizable redirect URLs after payment completion

## Requirements

- WordPress 4.0+
- WooCommerce 6.0+
- PHP 7.4+
- Flouci merchant account

## Installation

1. Download the plugin ZIP file
2. In your WordPress admin, go to **Plugins > Add New > Upload Plugin**
3. Upload the ZIP file and click **Install Now**
4. Activate the plugin
5. Go to **WooCommerce > Settings > Payments** and configure the Flouci gateway

## Configuration

1. Obtain your **App Token** and **App Secret** from your Flouci merchant dashboard
2. In WooCommerce settings:
   - Enable the Flouci payment gateway
   - Enter your App Token and App Secret
   - Customize the payment method title and description

## Support

For support or feature requests, please [open an issue](https://github.com/majdsassi/flouci-woocommerce/issues) on GitHub.

## Changelog

### 1.0
- Initial release with basic payment processing
- Support for Credit Cards, E-Dinar, and Flouci Wallet
- HPOS compatibility declaration
- Payment verification system

## License

GPL-2.0+ © [Majd Sassi](https://github.com/majdsassi)

---

**Note**: This plugin requires an active Flouci merchant account. Visit [Flouci's developer portal](https://developers.flouci.com) for more information.

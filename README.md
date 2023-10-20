# Lunar Online Payments for ThirtyBees

The software is provided “as is”, without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.

## Supported ThirtyBees versions

* *The plugin has been tested with most versions of ThirtyBees versions at every iteration. We recommend using the latest version of ThirtyBees, but if that is not possible for some reason, test the plugin with your ThirtyBees version and it would probably function properly.*

## Installation

Once you have installed ThirtyBees, follow these simple steps:

1. Signup at [lunar.app](https://lunar.app) (it’s free)
1. Create an account
1. Create an app key for your ThirtyBees website
1. Log in as administrator and upload the release zip under "Modules and services" -> Add a new module (plus icon in the top right corner).
1. It will be installed and you will be redirected to a list that contains the Lunar plugin. Click the Config button to go to the settings screen where you need to add the Public and App key that you can find in your Lunar account (https://lunar.app).

## Updating settings

Under the extension settings, you can:
 * Update the payment method name in the payment gateways list
 * Update the payment method description in the payment gateways list
 * Update the credit card logos that you want to show (you can change which one you accept under the lunar account).
 * Update the shop title & logo url that shows up in the hosted checkout page
 * Add app & public keys
 * Change the capture type (Instant/Delayed)


## Capturing, Refunding, Canceling

 * To `Capture` an order in delayed mode, use the status set in Lunar module settings (move the order to that status).
 * To `Refund` an order make sure you checked the "Refund Lunar" checkbox during the default procedure for **`Partial Refund`**.
    - Note: If for some reason the Refund procedure via Lunar fails, you will be notified and manual action will be required in your online Lunar account.
 * To `Cancel` an order move the order status to "Canceled".

These actions (Capture, Refund, Cancel) are also available in order view mode, `PROCESS LUNAR PAYMENT` box/section (Lunar Toolbox).

## Available features

1. Capture
   * ThirtyBees admin panel: full capture
   * Lunar admin panel: full/partial capture
2. Refund
   * ThirtyBees admin panel: full/partial refund
   * Lunar admin panel: full/partial refund
3. Cancel
   * ThirtyBees admin panel: full cancel
   * Lunar admin panel: full/partial cancel

=== Mollie Forms ===
Contributors: ndijkstra
Tags: mollie,registration,form,payments,ideal,bancontact,sofort,bitcoin,belfius,creditcard,recurring,forms
Requires at least: 3.8
Tested up to: 4.8.0
Stable tag: 0.3.13
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create registration forms with payment methods of Mollie. One-time and recurring payments are possible.

== Description ==

Create registration forms with payment methods of Mollie. One-time and recurring payments are possible.

= This is a Beta release, we are still working on more features. =



**Features:**

* Create your own forms
* Set extra fee's per payment method
* One-time and recurring payments
* Fixed or open amount possible
* Configure emails per form
* Refund payments and cancel subscriptions in Wordpress admin
* Available in English, Dutch and French.
* Style it with your own css classes.

More features will be added soon.


== Frequently Asked Questions ==

= Why can I only choose for One-time payments? =

For recurring payments you will need a supported payment method. You have to activate "SEPA Direct Debit" or "Creditcard" to use recurring payments.


== Screenshots ==

1. Form settings
2. Form
3. Registrations
4. Registration with subscription
5. Registration without subscription

== Installation ==

= Minimum Requirements =

* PHP version 5.3 or greater
* PHP extensions enabled: cURL, JSON
* WordPress 3.8 or greater


== Changelog ==

= 0.3.13 =
* Added check to prevent a payment without registration

= 0.3.12 =
* Bugfix when using multiple forms on 1 page

= 0.3.11 =
* <a> tag now possible in field label
* Label is now behind the checkbox

= 0.3.10 =
* Removed () when open amount is selected

= 0.3.9 =
* Bugfix multiple email adresses
* Added fixed variable {rfmp="form_title"} for Form title
* Added German language

= 0.3.8 =
* Bugfix

= 0.3.7 =
* Improved variables in emails
* Multiple email addresses possible seperated with comma (,)
* Fix for images in email

= 0.3.6 =
* Added consumer information (name, iban) to payments table
* Added fixed variable {rfmp="payment_id"} for Mollie Payment ID in email templates
* GET variables possible to prefill form: ?form_ID_field_INDEX=value (replace ID with form id and INDEX with the field index. First field is 0, second field is 1 etc.)

= 0.3.5 =
* Added "Number of times" option for subscriptions

= 0.3.3 =
* Tiny fix

= 0.3.2 =
* Fix subscriptions webhook

= 0.3.1 =
* Fixed issue with empty registrations
* Payment and subscription status visible in registration list
* Subscription table bugfix
* Added French translations

= 0.3.0 =
* You can now configure emails per form

= 0.2.3 =
* Using home url now instead of site url
* Fix for frequency label at open amount

= 0.2.2 =
* Registrations are now visible for every admin user

= 0.2.1 =
* Bugfix in open amount


= 0.2.0 =
* You can now add a price option with open amount so the customer can fill in an amount
* Bugfixes

= 0.1.9 =
* Fix for showing success/error message

= 0.1.8 =
* Bugfixes
* Checkbox added for recurring payments

= 0.1.7 =
* Language fix

= 0.1.6 =
* Bug fixes

= 0.1.5 =
* Bug fixes

= 0.1.4 =
* Bug fixes

= 0.1.3 =
* Bug fixes

= 0.1.2 =
* Bug fixes

= 0.1.1 =
* Bug fixes

= 0.1.0 =
* Beta release

== Upgrade Notice ==


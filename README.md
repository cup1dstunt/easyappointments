# Linnaeus University fork of EasyAppointments

This is the Linnaeus University ("Lnu") Library fork of the Easy!Appointments booking application. This README file is mainly for publishing and describing the changes made by Lnu to the original repository. If you're looking for the original Easy!Appointments README file, it can be found here: [README_ORIG.md](README_ORIG.md).

This is the [1.6.0-lnu](https://github.com/toekaa-lnu/easyappointments/tree/1.6.0-lnu) release, meaning that all Lnu features are implemented on top of the 1.6.0 release of Easy!Appointments. The older 1.5.2-lnu release can be found [here](https://github.com/toekaa-lnu/easyappointments/tree/1.5.2-lnu).

EasyAppointments is currently used at Lnu by two teams:
* Academic writing tutors, offering tutoring sessions to students in academic writing, presentation skills and study skills in Swedish and English. They had requirements for e.g. attaching files to bookings, appointment related custom fields, limits to the number of bookings, UI terminology changes and many others.
* Talking book support, enabling access to talking books for students with reading and writing difficulties. They had also their own requirements, like Zoom meeting support, OIDC login to bookings, terms and conditions as the first booking step, one-column layout and many others.

The purpose of this README is to document those changes so that others may benefit of what we have done. Hopefully someone else will find these useful too.

Here is a list of changes we have done so far. For each feature, there is a short description, and instructions how to get it into use in your own Easy!Appointments installation (using git).

1. **[Custom Field Improvements](#1-custom-field-improvements)**
2. **[Attached Files Support](#2-attached-files-support)**
3. **[Hide Provider Selection](#3-hide-provider-selection)**
4. **[Booking Advance Timeout Improvements](#4-booking-advance-timeout-improvements)**
5. **[Cooldown Period for Services](#5-cooldown-period-for-services)**
6. **[Hide Timezone from Customers](#6-hide-timezone-from-customers)**
7. **[Customer Booking Limits](#7-customer-booking-limits)**
8. **[Availability Marking in Calendar View](#8-availability-marking-in-calendar-view)**
9. **[Extended Permissions for Providers in Backend](#9-extended-permissions-for-providers-in-backend)**
10. **[Provider Colour in Backend Calendar](#10-provider-colour-in-backend-calendar)**
11. **[Services in Current Language First](#11-services-in-current-language-first)**
12. **[Custom Messages During Booking](#12-custom-messages-during-booking)**
13. **[Language URL Parameter Improvements](#13-language-url-parameter-improvements)**
14. **[Single Column for Booking Info](#14-single-column-for-booking-info)**
15. **[Custom Booking Step Order](#15-custom-booking-step-order)**
16. **[New Booking Step for Terms and Conditions](#16-new-booking-step-for-terms-and-conditions)**
17. **[Calendar Timegrid Settings](#17-calendar-timegrid-settings)**
18. **[Custom Logo Everywhere](#18-custom-logo-everywhere)**
19. **[Translatable Settings Texts](#19-translatable-settings-texts)**
20. **[Zoom Meetings Integration](#20-zoom-meetings-integration)**
21. **[OIDC Booking Login Integration](#21-oidc-booking-login-integration)**
22. **[Provider Booking Email Note](#22-provider-booking-email-note)**
23. **[Language Replacements Support](#23-language-replacements-support)**
24. **[Provider Daily Booking Limit](#24-provider-daily-booking-limit)**
25. **[Reply To Customer for Provider Email](#25-reply-to-customer-for-provider-email)**

There are also common instructions for taking these features into use, some problem situations and how to solve them, and finally a disclaimer:

* **[Required Commands and Commits](#required-commands-and-commits)**
* **[When Something goes Wrong](#when-something-goes-wrong)**
* **[General Disclaimer](#general-disclaimer)**


## 1. Custom Field Improvements

This feature improves the basic custom fields implementation in EasyAppointments. The custom fields are the extra fields that you can add to the customer information form, normally on the third page of the booking process. They are configured under *Admin > Settings > Booking Settings*. These are the improvements:

* **[Configurable max number of custom fields](#11-configurable-max-number-of-custom-fields)** : You can have more than the usual five custom fields, should you need them.
* **[Appointment-specific custom fields](#12-appointment-specific-custom-fields)** : The existing custom fields are user-specific, and updated every time the same user makes a new appointment. This is a whole new set of appointment-specific custom fields, so that different appointments from the same user can have their own values.
* **[Translation of field labels and values](#13-translation-of-field-labels-and-values)** : This adds translation support to the custom fields. You can use ID:s from the language translation files instead of just plain text.
* **[Support for additional input types](#14-support-for-additional-input-types)** : The standard custom fields supported only text. Now you can have numbers, drop down menus, checkboxes, radio buttons and so on.
* **[Support for HTML attributes](#15-support-for-html-attributes)** : You can specify HTML attributes for the custom fields, such as min/max values for numbers, and placeholders for text.
* **[Options for select drop-down menus](#16-options-for-select-drop-down-menus)** : Options for the drop down menus (HTML "select" element) can be configured in a flexible way using the language translation files.
* **[Handling groups of checkboxes and radio buttons](#17-handling-groups-of-checkboxes-and-radio-buttons)** : Groups of checkboxes and radio buttons can be handled in a similar way to the drop down menus.

There is some configuration involved when taking this change into use so please also read the **[How to Add This Feature to Your Build](#18-how-to-add-this-feature-to-your-build)** section.


### 1.1. Configurable Max Number of Custom Fields

The number of custom fields is now configurable in the `config.php` file (in the root folder).
```php
  const MAX_CUSTOM_FIELDS = 5;
```
This value affects the existing custom fields, where the maximum number has so far been 5, but you can change it to something higher if you think that you may need more custom fields at some point.

If you change this, make sure you do it **before** running the migration command to update the database tables. See **[How to Add This Feature to Your Build](#18-how-to-add-this-feature-to-your-build)** for more info.

These custom fields are customer-specific, and stored in the `ea_users` table in the Easy!Appointments database. This means that they are unique to each user, and shared between all the same user's appointments. This is fine for fields such as *address* and *age* which are properties of the user, however can't be used for custom fields that need to be different for each booking.


### 1.2. Appointment-specific Custom Fields 

Since the existing custom fields are updated every time a user makes a new booking, there is sometimes a need for appointment-specific custom fields. These are stored in the `ea_appointments` table in the Easy!Appointments database, so they are unique for each booking. This makes it possible to add fields such as *number of participants* or *appointment notes*.

Like the customer-specific custom fields, the max number of appointment-specific custom fields is also configurable in the `config.php` file (in the root folder).
```php
  const MAX_APPOINTMENT_CUSTOM_FIELDS = 5;
```
Make sure you change this **before** running the migration command to update the database tables. See **[How to Add This Feature to Your Build](#18-how-to-add-this-feature-to-your-build)** for more info.

The new appointment-specific custom fields will appear in the *Admin > Settings > Booking Settings* UI, with the same functionality as the existing custom fields.


### 1.3. Translation of Field Labels and Values

You can still write plain text as the label of the custom fields as before, but you can now also type in an ID defined in the translation files. This makes it possible to have the label translated to different languages in the booking UI.

Let's assume that you have the following entries in the language translation files.

```php
# In application/language/english/translations_lang.php:
$lang['label_age'] = 'Age';

# In application/language/swedish/translations_lang.php:
$lang['label_age'] = 'Ålder';
```

You can now type in `label_age` as the label in a custom field:
```json
label_age
```

When the booking UI is in English the label will say *Age* and when in Swedish it will say *Ålder*.


### 1.4. Support for Additional Input Types

The existing custom fields are limited to just `text` inputs types. Now you can add an optional configuration block to make it possible to use other types of input fields. The configuration block is added to the label field, after the label text itself. For example, you can type the following into the label field:
```json
label_age {"type":"number"}
```
Here the label text is `label_age` and the configuration block `{"type":"number"}`. If you have some experience in coding you may recognize the configuration block as a JSON structure, but that's not really important. The important bits to know are that the configuration starts with a curly brace `{` and ends with a curly brace `}`, and in between those curly braces it has a key-value pair (both key and value are in quotation marks `"`, with a colon `:` between them). In the example above the key is `type` and value is `number`, which means that the type of this custom field should be number.

The `number` type is one of the standard HTML input types. When using this type the input to restricted to only digits. The custom field will also have up/down arrows so that the value can be changed with the mouse.

This is just one of many types that can be used. The following HTML input types are currently supported:
* `checkbox` (see [Handling groups of checkboxes and radio buttons](#17-handling-groups-of-checkboxes-and-radio-buttons))
* `color`
* `date`
* `email`
* `month`
* `number` (see [Support for HTML attributes](#15-support-for-html-attributes))
* `password`
* `radio` (see [Handling groups of checkboxes and radio buttons](#17-handling-groups-of-checkboxes-and-radio-buttons))
* `range` (see [Support for HTML attributes](#15-support-for-html-attributes))
* `tel`
* `text`
* `time`
* `url`
* `week`

In addition to the HTML input types, the following form elements can also be given as `type`:
* `textarea` (see [Support for HTML attributes](#15-support-for-html-attributes))
* `select` (see [Options for select drop-down menus](#16-options-for-select-drop-down-menus))


### 1.5. Support for HTML Attributes

The configuration is not limited to just specifying the `type` of the custom field. You can have other key/value pairs in the same configuration, just separate them with a comma `,`. For example, you can define a placeholder for text fields, or default values for number fields. See the descriptions and examples below.

With the `attributes` key you can directly enter the attributes for the HTML input element. This is useful for example when setting min/max values for number inputs, or number of rows for a textarea.

For example, to set limits and initial value for a `number` input, you can enter this into the label field:
```json
label_age {"type":"number","attributes":"min=18 max=99 value=18"}
```

To set a number of rows for a `textarea`, and the maximum number of characters entered:
```json
label_profession {"type":"textarea","attributes":"rows=4 maxLength=500"}
```

The `attributes` key can be used to enter more or less anything you want directly as attributes in the HTML element. **Be careful not to enter anything that might break the HTML code, such as a `>` tag ending character.**

One HTML attribute that has its own key/value pair is the `placeholder`. It is used to show a prompt or initial value in a `text` or `textarea` custom field, and will automatically disappear when the user starts typing into the field.

The reason for having `placeholder` as its own key/value pair in the configuration block is to make it possible to use a translation ID as the placeholder and then get it translated to different languages in the UI. Of course you can use it just with plain text too, if you don't need any translations.

For example, to define the label and the `placeholder` for a `text` type using plain text:
```json
Your Profession {"type":"text","placeholder":"Please enter your current profession"}
```

To use translation ID:s instead of plain text, and `textarea` type instead of `text`:
```json
label_profession {"type":"textarea","placeholder":"placeholder_profession"}
```

For the latter example to work, you of course need to have the ID `placeholder_profession` defined in the translation file. For example, you can have the following entry in `application/language/english/translations_lang.php`:
```php
$lang['placeholder_profession'] = 'Please enter your current profession';
```


### 1.6. Options for Select Drop-down Menus

When the `type` of the custom field is `select`, a drop down menu is created with a number of options to choose from. The options are created automatically based on the translation ID used as the label.

Given the following entries in a translation file (eg. `application/language/english/translations_lang.php`):
```php
$lang['custom_field_pulldown_menu'] = 'Pulldown menu custom field';
$lang['custom_field_pulldown_menu_prompt'] = 'Select option from menu...';
$lang['custom_field_pulldown_menu_1'] = 'First option';
$lang['custom_field_pulldown_menu_2'] = 'Second option';
$lang['custom_field_pulldown_menu_3'] = 'Third option';
$lang['custom_field_pulldown_menu_last'] = 'Other';
```

A dropdown menu custom field can then be defined as follows:
```json
custom_field_pulldown_menu {"type":"select", "sort":"false"}
```
The ID `custom_field_pulldown_menu` (with the translation *Pulldown menu custom field*) is used as the label. This ID is then used as a base ID for the options. The options are automatically created based on other ID:s having the same beginning as the base ID:

1. If there is an ID with `_prompt` added to the base ID, it is used as the initial value in the menu, but it is not selectable. It is just used for prompting the user to select a value from the menu. If there is no such ID, the first option is used instead as the initial value.

2. The actual options are created in the similar way. The ID of the first option has `_1` added to the base ID, the second option `_2` and so on. The number of options is automatically detected based on the ID:s defined in the translation files.

    If the configuration includes `"sort":"true"`, the list of options is sorted according to the translations of the current language.

3. Finally, if there is an ID with `_last` at the end of the base ID, it is added as the last item (regardless of the sorting of the other options). This is convenient to use for options such as *Other* or *Don't know*.

Note that using translation ID:s is required for this to work, so you can't just enter plain text as the label of a `select` custom field.


### 1.7. Handling Groups of Checkboxes and Radio Buttons

Handling a group of checkboxes and radio buttons is similar to how options for a select drop-down menu are handled.

Say that you have the following entries in a translation file (eg. `application/language/english/translations_lang.php`):
```php
$lang['custom_field_fruit'] = 'Fruit';
$lang['custom_field_fruit_1'] = 'Banana';
$lang['custom_field_fruit_2'] = 'Apple';
$lang['custom_field_fruit_3'] = 'Pear';
$lang['custom_field_fruit_last'] = 'None of the above';
```

A group of radio buttons can then be defined as a custom field as follows:
```json
custom_field_fruit {"type":"radio", "sort":"true"}
```

A group of checkboxes can be defined in almost the same way:
```json
custom_field_fruit {"type":"checkbox", "sort":"true"}
```

The difference between radio buttons and checkboxes is that you can select several checkboxes in a group while only one radio button in a group can be selected.

Just like with [options for select drop-down menus](#16-options-for-select-drop-down-menus), the number of checkboxes and radio buttons is automatically detected based on the ID:s in the translation files. Use the ID for the custom field label as a base ID, and then add `_1`, `_2`, `_3` (and so on) to the end of the base ID to define the items. There is a `_last` item too, but no `_prompt` item (not really needed, as all the items are visible at all times).


### 1.8. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick f299d34a6b0d1d4a7f1aede40db1edfc71f3eb4c
   ```
3. If you think that you might need more than the default number of custom fields (default is 5 user and appointment fields each), edit the `config.php` file in the root folder in your Easy!Appointments installation, and add the following two lines (change the values to the number of fields that you want):
   ```php
   const MAX_CUSTOM_FIELDS = 5;
   const MAX_APPOINTMENT_CUSTOM_FIELDS = 5;
   ```
   These are just upper limits, you don't actually have to enable all of them at once.

4. Finally, migrate the database to include the new setting:
   ```bash
   php index.php console migrate
   ```

### 1.9 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down custom_fields
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert f299d34a6b0d1d4a7f1aede40db1edfc71f3eb4c
   ```
3. If you want, you can edit out the *MAX_CUSTOM_FIELDS* and *MAX_APPOINTMENT_CUSTOM_FIELDS* constants from the `config.php` file, but it's not necessary.


## 2. Attached Files Support

This feature makes it possible for customers to attach files to the booking.

A number of settings are added to *Admin > Settings > Booking Settings* to configure this feature:
* **Attached Files** : This is the main switch to toggle support for attached files on and off. When activated, the following subsettings become available:
* **Maximum number of files** : The customer can add up to the maximum number of files when creating a booking. When updating an existing booking, the customer can attach new files and discard previously attached files (provided that the total max number is not exceeded). The provider can add and discard attached files in a similar way when adding or editing a booking in the calendar view in the admin panel.
* **Maximum Size of a File (bytes)** : Set the maximum size for one attached file. The check is done in the backend when confirming the booking.
* **Allowed File Types** : This is a comma-separated list of file extensions and mime types, and it defines the types of files that the customer is allowed to attach. The check is done with extension when attaching the file in the frontend. Another (more reliable) check is done with mime types in the backend when confirming the booking. The backend check is converting the file extensions to mime types too. 
* **Description of Allowed File Types to Customer** : Here you can set the text shown to the user in the booking form about allowed file types. The same text is also used as part of the error message if an unallowed file type is attached. This can be an ID into the translation files (under `application/language/*/translations_lang.php`), or simply a plain text string if you don't need any translations.

The emails that are sent after creating or editing a booking include information about the attached files, but the actual files themselves are not attached.

There is some optional configuration involved when taking this change into use so please also read the **[How to Add This Feature to Your Build](#21-how-to-add-this-feature-to-your-build)** section below.

### 2.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick 0ef3585aface669bdbbc86914ec90bafd4514666
   ```
3. If you think that you might need more than the default number of attached files (default is 10), edit the `config.php` file in the root folder in your Easy!Appointments installation, and add the following line (change the value to the max number of files that you want):
   ```php
   const MAX_ATTACHED_FILES = 10;
   ```
   This is just an upper limit. You'll define the actual allowed number of files in *Settings > Booking Settings*.

4. Finally, migrate the database to include the new setting:
   ```bash
   php index.php console migrate
   ```

### 2.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down attached_files
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert 0ef3585aface669bdbbc86914ec90bafd4514666
   ```


## 3. Hide Provider Selection

This feature makes it possible to hide the provider selection from customers in the booking UI, so that only the service needs to be selected.

This can be configured with two new settings under *Admin > Settings > Booking Settings*, added as subsettings under the existing *Any Provider* setting:
* **Hide provider selection** : Activating this setting will hide the *Provider* selection completely from the booking UI. The effect is the same as always selecting the *Any provider* setting if there's multiple providers for a service (or the one and only provider if only one is available).
* **Provider selection method** : A new algorithm is added for selecting the provider when *Any provider* is selected. The legacy one (now selectable as *Available on date* in the Settings UI) was only looking at the date of the new booking, and selected the provider that had the most available periods on that date. The new algorithm *Available around booking* also looks at dates around the new booking, and selects the provider with the longest availability around the new booking. This works better when providers only have at most one or two available timeslots per day.

When the booking is confirmed, a provider is selected by the algorithm and assigned to the appointment. The e-mail sent to the customer includes the name of the selected provider.

> For a more detailed description, the new *Available around booking* algorithm uses the following steps to select a provider:
> 1. It gets a list of providers available for the selected date and time.
> 2. For each of these providers, it chooses their existing appointment which is closest in time to the new booking.
> 3. Among all these closest appointments, it finds the provider having the appointment furthest away from the new booking.
>
> The goal is to distribute the bookings among the providers as evenly as possible over time. Of course the algorithm is only used when there actually are multiple providers available for a booked timeslot.


### 3.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick e4e0af4860eadd56aafe65a3989bfeb8726dc7a8
   ```
3. Finally, migrate the database to include the new setting:
   ```bash
   php index.php console migrate
   ```

### 3.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down hide_provider_selection
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert e4e0af4860eadd56aafe65a3989bfeb8726dc7a8
   ```


## 4. Booking Advance Timeout Improvements

This feature makes it possible to specify a time unit When configuring the booking advance timeout ("booking lead time"). The existing implementation only supported minutes, but now eg. weekdays can be used. In addition, the timeout can now be different for new bookings and rebookings/cancellations.

Two new settings are added in *Admin > Settings > Business Logic*, under the existing *Allow Booking/Rescheduling/Cancellation Before* setting.

The old single timeout setting (*Book Advance Timeout*) is now split into two settings: one for new bookings (*New Booking Advance Timeout*) and one for rebookings and cancellations (*Rebooking/Cancellation Advance Timeout*). This makes it possible eg. to require new bookings to be done well in advance, but allow cancellations at the last minute (which is maybe still better than the customer not showing up at all).

Also a new **Book Advance Timeout Unit** setting is added in *Admin > Settings > Business Logic*, under the existing *Allow Booking/Rescheduling/Cancellation Before* setting. Now it is possible to select the time unit from the following options:
* minutes
* hours
* days
* weekdays

### 4.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick e17995c76b1e3af46147262e8f9ff510e4d6a7b7
   ```
3. Finally, migrate the database to include the new setting:
   ```bash
   php index.php console migrate
   ```

### 4.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down book_advance_timeout_unit
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert e17995c76b1e3af46147262e8f9ff510e4d6a7b7
   ```


## 5. Cooldown period for services

A new **Cooldown** setting is added for each service under *Admin > Services*. The cooldown is a short time at the end of the appointment, where the provider can wrap up the meeting (make notes, have a cup of coffee, go to toilet...). The time booked in the provider's calendar has the full duration (including the cooldown), but the duration shown to the customer does not include the cooldown.

For example, when the customer books a service with a 60 minute duration and 15 minute cooldown, the duration communicated to the customer is 45 minutes (in the booking UI service selection and confirmation screens, and in the confirmation email), but the appointment occupies a full 60-minute timeslot in the calendar. 

Easy!Appointments 1.6.0 introduced a *Slot interval (minutes)* setting, which can in certain conditions be used to accomplish the same function: a 45-minute service having a 60-minute slot interval would leave a 15-minute gap between bookings. But those gaps could potentially still be bookable, eg. by another service with a 15-minute duration, or via the backend calendar. So the cooldown is maybe still a safer option to use for this purpose.

### 5.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick 8175a85ebab388255c462ca1051ad78334f1f75b
   ```
3. Finally, migrate the database to include the new setting:
   ```bash
   php index.php console migrate
   ```

### 5.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down cooldown
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert 8175a85ebab388255c462ca1051ad78334f1f75b
   ```


## 6. Hide Timezone from Customers

A new setting **Hide Customer Timezone** is added under *Admin > Settings > Booking Settings*. When activated, customers are no longer able to change (or even see) the timezone selection in the booking UI. Instead, the *Default Timezone* (as defined under *Admin > Settings > General Settings*) is always used for new bookings.

The timezone is hidden from the date/time selection and confirmation steps in the booking UI, as well as the email sent out for saved and deleted appointments. Providers are still able to view (and change) their timezone under *Users > Providers*, and when accessing bookings via the calendar page.

### 6.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick af739697259163238660f2574f619d70e273a991
   ```
3. Finally, migrate the database to include the new setting:
   ```bash
   php index.php console migrate
   ```

### 6.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down hide_customer_timezone
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert af739697259163238660f2574f619d70e273a991
   ```


## 7. Customer Booking Limits

With this feature it is possible to set limits to how many bookings each customer can make.

Two new *Customer Booking Limit* settings are added under *Admin > Settings > Business Logic*. The new settings control how many appointments each customer is allowed to book during a time period. There is also a third setting for selecting the time unit.

The new settings are:
* **Maximum number of appointments** : Controls the total number of appointments (past, present and future) that a customer is allowed to make during the selected time period. These appointments can be for different services.
* **Active bookings per service** : Controls how many active (ongoing and future) bookings a customer can have for each service, during the selected time period.
* **Time period for customer booking limits** controls what time period is used for the above two settings. You can set it to one of the following:
  * Day
  * Week
  * Month
  * Half-year (with two distinct periods January..June and July..December)
  * Calendar year (with the period January..December)
  * School year (with the period July..June)

The limits are reset at the start of each new time period, so if you choose eg. *Day* as the time period, then the customer is allowed to book the selected number of appointments each day.

In Easy!Appointments, a customer is identified by the email address.

### 7.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick a0121bd80448002cade9402c2e2084ad2d313643
   ```
3. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 7.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down customer_booking_limits
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert a0121bd80448002cade9402c2e2084ad2d313643
   ```


## 8. Availability Marking in Calendar View

This feature adds the possibility to mark *Availability* in the *Admin > Calendar*, in the same way that *Unavailability* or *Appointment* is marked. Just use the mouse to drag & select a timespan and click on *Availability* in the popup that appears.

A *Working Plan Exception* is created behind the scenes, with the selected timespan marked as available. If the marked day has an existing schema, it is used as the starting point for the Working Plan Exception. It is possible to select multiple timespans for the same day, and any existing Working Plan Exception is adjusted accordingly, with breaks added between availabilities as needed.

This makes it easy to add working plan exceptions, but it's especially handy for providers who want to have a totally closed calendar as a starting point and then just add available slots manually. This is how the tutors at Lnu want to do it.


### 8.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick f7226aa6121848d0e1cfbc43306d0558ee36f465
   ```

No database migration is required for this feature.

### 8.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following command in the root of your Easy!Appointments installation.

1. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert f7226aa6121848d0e1cfbc43306d0558ee36f465
   ```


## 9. Extended Permissions for Providers in Backend

A new setting **Extended Backend Permissions for Providers** is added under *Admin > Settings > Booking Settings*.

When this setting is enabled, providers can:
* Access (view, edit and delete) also each other's bookings in the calendar view. By default, providers are only able to access their own bookings. This is handy eg. in case of sickness, when one provider needs to take over an existing booking assigned to another provider.
* Access (view and edit) their own account including Work Schedule under *Admin > Account*. 
* Access (view and edit) Services.

### 9.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick 6ce6112b066d44aaf8b3e000f938405eb9695465
   ```
3. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 9.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down provider_extended_backend_permissions
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert 6ce6112b066d44aaf8b3e000f938405eb9695465
   ```


## 10. Provider Colour in Backend Calendar

This feature lets each provider mark their appointments in the calendar with their own colour. A new **Color** setting is added under provider account settings, and this colour will be shown as a narrow column on the right edge of the appointment. This helps to quickly identify which appointments belong to which providers, especially when filtering with "All" providers in the calendar view.

The service/appointment colour is still shown as the event background colour, as before.

### 10.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick a846204b0af51d7df75eb2768406f5bd376f021e
   ```
3. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 10.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down provider_color
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert a846204b0af51d7df75eb2768406f5bd376f021e
   ```


## 11. Services in Current Language First

This feature modifies the service selection in the booking UI so that it is possible to show the services in the current language on the top of the list. For this to work, the service categories need to be setup so that they match the language names. If you want to use service categories for some other purpose, then this solution will not work for you.

For example, if you have made the languages *English* and *Swedish* the only languages available in the `application/config.php` file, you can also define service categories *English* and *Swedish*, and then assign services in English the *English* category, and services in Swedish the *Swedish* category. A customer who is using the booking UI in English, will see the services in English first in the service selection list (and another customer who is using the UI in Swedish, the Swedish services first). 

A new setting **Services in Current Language Shown First** is added under *Admin > Settings > Booking Settings* for activating/deactivating this feature.


### 11.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick a7f23dced459cf407d93dfd8b36f99a2908aa181
   ```

No database migration is required for this feature.

### 11.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following command in the root of your Easy!Appointments installation.

1. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert a7f23dced459cf407d93dfd8b36f99a2908aa181
   ```


## 12. Custom Messages during Booking

This feature lets some custom messages to be added to certain predifined locations in the booking UI. There are two custom messages and a custom link/text:
* **Message on Service/Provider Page**  
This is a message shown on the service/provider selection page (normally the first step of the booking process). It is shown in its own frame with a background colour that makes it stand out from the rest of the page. At Lnu, we use it for informing students with reading/writing difficulties about contacting a special teacher.
* **Message on Date/Time Unavailability**  
This is an extra message shown when the current month has no available time slots. At Lnu we use it to inform students that there might not be any available timeslots att all and encourage them to check back at a later time.
* **Link on Confirmation Page**  
This is a custom link that is shown on the confirmation page informing the customer that the booking is successful. It replaces the button that are usually shown on that page ("Go to Booking Page"). At Lnu we use it for directing the student to the service homepage, since it's unlikely that they would want to make a new booking right away.
* **Confirmation Page Link Text**  
This is the text displayed for the link "Link on Confirmation Page".

A new setting **Custom Booking Messages/Links** is added under *Admin > Settings > Booking Settings*. When the setting is disabled, all custom messages are deactivated. If the setting is enabled, each message can be activated individually by entering either an ID from the language translation files, or literal plain text, into the text field.

If the text field matches an ID defined in the translation file for the current user language, the resolved translation is shown - this way, you can choose to show a message only in selected languages, by only defining that ID's translation for those languages. If the text field doesn't match any defined translation ID, it's shown as plain text as-is, regardless of the current user language. If the text field for each setting is left empty, then the message is not shown for any language.


### 12.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick 8764b45d5d870035f9a353382185e0c273605f1b
   ```
3. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 12.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down custom_messages_during_booking
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert 8764b45d5d870035f9a353382185e0c273605f1b
   ```


## 13. Language URL Parameter Improvements

This feature improves on the existing language URL parameter in Easy!Appointments. 

When present, the parameter is used to change the language and then a redirect is done, with the parameter removed from the URL. This makes it work better together with the language selection button in the footer. The URL parameter also now takes priority over any language stored in the session variable (and is written to the session variable too).

The parameter URL key can be either `language` or `lang`. The value is one of the language names (eg. `english` or `swedish`) or one of the language codes (eg. `en` or `sv`). The mapping of language codes to names is done using a table in `application/config/config.php`.

For example, the following variants can be used for starting the booking UI in English:
```
https://demo.easyappointments.org/?language=english
https://demo.easyappointments.org/?language=en
https://demo.easyappointments.org/?lang=english
https://demo.easyappointments.org/?lang=en
```

### 13.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick 446caef3ba009b78406a68348c5874a0773cd0a8
   ```

No database migration is required for this feature.

### 13.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following command in the root of your Easy!Appointments installation.

1. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert 446caef3ba009b78406a68348c5874a0773cd0a8
   ```


## 14. Single Column for Booking Info

This feature enforces the *info* and *confirmation* steps in the booking UI to always use a single-column layout. Normally, the fields are divided into two columns (if there's enough fields for both columns).

A new setting **Single Column Layout for Info and Confirmation Steps** is added under *Admin > Settings > Booking Settings* that can be used to enable or disable this feature.

### 14.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick d1f256e283f22fde6f962c196cacc1b07c436e57
   ```
3. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 14.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down booking_info_single_column
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert d1f256e283f22fde6f962c196cacc1b07c436e57
   ```


## 15. Custom Booking Step Order

This feature allows the steps in the booking UI to be reordered and even new steps (to be implemented separately) added to the booking process. Some restrictions must be considered when reordering:
1. The service/provider selection step must always come before the date/time selection step (if the system doesn't know how is the provider, it can't know what dates and times are available).
2. The confirmation step must always be the last one (it makes no sense having extra steps after the confirmation).

A new setting **Custom Booking Step Order** is added under *Admin > Settings > Booking Settings* that can be used to edit the step order. This is a text field, with the steps written in order, separated by '>'. The following steps are available by default:
* service
* time
* info
* confirmation

For example, `service>time>info>confirmation` would match the default booking step order. All four default steps must be included exactly once. In case of an unknown step or other kind of syntax error, the default order is used as a fallback.

There is not a lot to reorder with the default steps: mainly the info step can be shown before the service and time steps. However, this is exactly what one of our teams at Lnu wanted. They also wanted an extra step: [Terms and Conditions to be shown as its own step](#16-new-booking-step-for-terms-and-conditions) before anything else.

### 15.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick 40ff48c5d77af038a175338ecae736f4ace5dae5
   ```
3. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 15.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down booking_step_order
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert 40ff48c5d77af038a175338ecae736f4ace5dae5
   ```


## 16. New Booking Step for Terms and Conditions

This feature makes it possible to show the Terms and Conditions as its own step in the booking UI. The customer needs to accept the terms in order to continue to the next booking step.

The step where the Terms are shown is determined by the **Custom Booking Step Order** setting under *Admin > Settings > Booking Settings*, introduced in the feature **[Custom Booking Step Order](#15-custom-booking-step-order)**. This new step is called "terms", so just insert it where you want to have it. At Lnu, one of our teams wanted to have the Terms and Conditions as the first step, and Customer Information as the second, so their Custom Booking Step Order setting is `terms>info>service>time>confirmation`.

The text for the Terms and Conditions is fetched from the existing setting under *Admin > Settings > Legal Contents > Terms & Conditions*. Here you can also choose to show it in the confirmation step as before (as a checbox and a link), independently of having it as its own step. You could even have both (although that probably wouldn't make much sense).

### 16.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. This feature is dependent of the **[Custom Booking Step Order](#15-custom-booking-step-order)** commit, so add also that if you haven't done it yet.

3. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick adae1602117df3469a96cf31a5f17187deb178fd
   ```

No database migration is required for this feature.

### 16.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following command in the root of your Easy!Appointments installation.

1. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert adae1602117df3469a96cf31a5f17187deb178fd
   ```


## 17. Calendar Timegrid Settings

This feature adds some settings to make it easier to see a whole backend calendar working week on the screen, without having to scroll. This reduces the risk of not noticing events in the calendar by mistake.

Five new settings are added under **Calendar**, in *Admin > Settings > General Settings*:
* **Calendar Start Time**, the earliest time shown by the calendar. It is not even possible to scroll before this time, meaning that it's not possible to add bookings before this time.
* **Calendar End Time**, the latest time shown by the calenda. It is not even possible to scroll past this time, meaning that it's not possible to add bookings after this time.
* **Calendar Scroll Time**, the time that the calendar starts with at the top. It is still possible to scroll up and down, within the limits of Calendar Start Time and Calendar End Time.
* **Hide Weekends in Calendar**, hides the weekends so that only the working week is shown.
* **Calendar Time Slot Height**, a value in HTML/CSS terms that controls how high each timeslot (15 minutes) in the calendar is. The default value is "1rem". Use something smaller (eg. "0.9rem") to show more hours in the same vertical space.

### 17.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick 4bf0566a481cab509441f44a915319bbe84102ce
   ```
3. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 17.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down calendar_display_settings
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert 4bf0566a481cab509441f44a915319bbe84102ce
   ```


## 18. Custom Logo Everywhere

This feature expands on the existing **Company Logo** setting under *Admin > Settings > General Settings*. Even when a company logo was selected, the default Easy!Appointments logo was still used in a number of places:
* Emails
* Backend header
* Login/logout screens
* Password reset and recovery screens

After adding this feature the company logo will replace the default logo in all those cases.

### 18.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick e5077edad3ab0d1e35bc2a7599f3a8b35e42f3cb
   ```

No database migration is required for this feature.

### 18.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following command in the root of your Easy!Appointments installation.

1. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert e5077edad3ab0d1e35bc2a7599f3a8b35e42f3cb
   ```


## 19. Translatable Settings Texts

This feature enables translation support for all text-based settings. Instead of just writing plain text into the text field, you can now also use an ID in the translation files.

The system detects automatically if the text added is an ID or plain text. If there is a key in the translation tables matching the given ID, the translated text is used. If no match is found, the text is shown as it is.

When using a translation ID in an HTML-based field, make sure to remove any HTML formatting that is automatically added to the text. Use the "<>" (View HTML) button to view the raw HTML code and remove any added tags, so that only the ID remains.

This affects the following settings:
* Company Name
* Company Email
* Company Link
* Cookie Notice
* Terms & Conditions
* Privacy Policy
* Legal Notice

### 19.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick b7a231e30f2a6b7967b789ebd13dfe93f734f6f3
   ```

No database migration is required for this feature.

### 19.2 How to Remove This Feature from Your Build

In case you have tested the feature and decide that you don't want to have it after all, you can run the following command in the root of your Easy!Appointments installation.

1. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert b7a231e30f2a6b7967b789ebd13dfe93f734f6f3
   ```


## 20. Zoom Meetings Integration

This feature adds support for Zoom meetings. When enabled (and activated for a provider), two Zoom links are automatically added to the provider's bookings:
* A join link (for the customer, stored as the Meeting Link in the appointment)
* A start (host) link (used by the provider for starting the meeting). The host link is included in the provider's email and in the appointment modal in the calendar, but never shown to the customer.

If the appointment is moved or deleted, the Zoom meeting is moved and deleted as well. If the meeting is assigned to another provider, the Zoom meeting is not modified. This is done on purpose, in order not to change the Zoom link to the customer, and increase the risk of the customer missing the meeting. However, the side effect is that the original provider is still the host (and will get reminders for the meeting from Zoom). The new provider should use the normal meeting (join) link instead, since whoever will use the host will appear as the original provider.

The feature is added under *Admin > Settings > Integrations* as **Zoom**, in a similar way as the Jitsi or Google meeting features. To configure it, you need to create a *Server-to-Server OAuth* app in the Zoom App Marketplace and copy its Client ID, Client Secret and Account ID to the settings here. The app needs to have permission to view, edit and delete meetings.

After enabling the feature in the integration settings, it also needs to be activated for each provider that wants to use it. In the provider account settings, under the *Options* section, there is now a *Create Zoom Links* option that each provider can use to activate or deactivate it for their own bookings.

### 20.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick d2f7e5b073500d8dbccc010f08568fe5fe09edfb
   ```

3. Optional (but recommended): Add your own encryption key for encrypting the Zoom Client Secret in the database. If you already added the encryption key when adding the [OIDC](#21-oidc-booking-login-integration) feature commit, you can use the same key here. First, run the following command to create a random key with 32 hexadecimal characters. 
   ```
   php -r "echo bin2hex(random_bytes(16));"
   ```
   Then, add the following line to the `config.php` file in the root of your Easy!Appointments installation, replacing <my_encryption_key> with the actual key generated by the previous command:
   ```
   const ENCRYPTION_KEY ="<my_encryption_key>";
   ```
   If you ever change this key, you need to re-save the Client Secret.

4. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 20.2 How to Remove This Feature from Your Build

In case you have tested this feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down zoom_meeting_links
   ```

2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert d2f7e5b073500d8dbccc010f08568fe5fe09edfb
   ```


## 21. OIDC Booking Login Integration

This feature adds an OIDC client to Easy!Appointments, which makes it possible to require login with the *OpenID Connect* standard. Currently only login to the booking UI is supported, login to backend is not yet implemented.

The feature is configured under *Admin > Integrations > OIDC*. You need to setup Easy!Appointments as a client in your OIDC identity provider, and copy **Client ID**, **Client Secret** and **Identity Provider URL** to the settings in the OIDC integration.

After a successful login, the OIDC identity provider returns properties of the user ("claims" in OIDC terminology), like name, e-mail and affiliation. Some of these are standard, some are dependent on your OIDC identity provider setup. These properties can be used to further limit access to the booking UI, and also to autofill parts of the customer info in the booking UI.

Limiting the access beyond just a successful login is done with a rules statement in the **User Property Restrictions** setting. This is a text field with one or more rules having the format "property_name=value1|value2;". All rules must match, values can have alternatives. For example, to allow access only to students from the mathematics or physics departments, the following rules statement can be used: "affiliation=student;department=mathematics|physics". The property names and values that you can use of course depend on what is made available in your OIDC identity provider implementation. This client is asking for the *openid*, *profile* and *email* scopes, which also controls what the identity provider returns to the client.

If the user is denied login due to the rules in the User Property Restrictions setting, an error message is shown. The title of that message can be set with the **Rejection Message Title** setting and the actual text with the **Rejection Message Text** setting. Both of these can be either plain text or IDs in the translation files.

When autofilling Customer Information fields in the booking UI, fields are matched with the OIDC properties. Standard OIDC properties are eg. "given_name", "family_name" and "email", with the first two having duplicate names "first_name" and "last_name" added.

These properties are used for autofilling some of the fields in the Customer Information step in the booking UI, for example the standard user fields *First Name*, *Last Name* and *Email*. It is possible to add autofill to the custom fields too (when the **[Custom Field Improvements](#1-custom-field-improvements)** feature is also included). Add an *auth-prop* attribute to the custom field (e.g. *auth-prop="affiliation"*), and it will be autofilled with the value of the OIDC property "affiliation", if such a property is returned by the identity provider for the logged-in user. This also works for drop-down menus (select), radio buttons and checkboxes, provided that there exists an option matching the OIDC property value. The autofilled fields in the booking UI will also be locked, so the user is not able to edit them.

The OIDC session expiry is configured in the Identity Provider. Since OIDC is a Single Sign-On (SSO) authentication, there is both an SSO session (for the user) and a client session (for the Easy!Appointments client). Client Session Idle sets the maximum time the user can have the client open without actually doing anything, and it is reset whenever stepping back/forward in the booking UI. Client Session Max sets the maximum time the user can have the client open, either idle or actively using it. At Lnu we have Client Session Idle at 15 minutes, and Client Session Max at 1 hour. If either of these expire, the booking is restarted, and the login is required again. However, by default the user's SSO session is still active at this point, and so the Identity Provider will use it to automatically log the user back into the Easy!Appointments client, without the user having to enter the login credentials at all. The booking UI is still restarted from scratch.

The setting **SSO Logout After Booking and Session Expiry** will log the user out from the SSO session too, when the client session expires and also when the booking is completed. This will prevent the automatic login from happening, and will force the user to enter the login credentials again. This setting is recommended to be enabled when the booking UI is ever used on a public or shared computer.

### 21.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick f7534a76004c79154bd2dde1cd6ca04c3992d0c1
   ```

3. Optional (but recommended): Add an encryption key for encrypting the Zoom Client Secret in the database. If you already added the encryption key when adding the [Zoom](#20-zoom-meetings-integration) feature commit, you can use the same key here. First, run the following command to create a random key with 32 hexadecimal characters.
   ```
   php -r "echo bin2hex(random_bytes(16));"
   ```
   Then, add the following line to the `config.php` file in the root of your Easy!Appointments installation, replacing <my_encryption_key> with the actual key generated by the previous command:
   ```
   const ENCRYPTION_KEY ="<my_encryption_key>";
   ```
   If you ever change this key, you need to re-save the Client Secret.

4. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 21.2 How to Remove This Feature from Your Build

In case you have tested this feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down oidc_booking_login
   ```

2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert f7534a76004c79154bd2dde1cd6ca04c3992d0c1
   ```


## 22. Provider Booking Email Note

This feature makes it possible for each provider to add their own custom note to the emails for new and changed bookings. This note can be anything the provider wants to inform the customer about: for example about things to prepare before the meeting, how the meeting is setup, or just a friendly welcome message.

The note is edited in the provider information, under the **Booking Email Note** setting. Providers who don't want to have any custom note added to the email, can just leave this field blank.

### 22.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick d42c8f335bd110f353d4fa3fd13ffcf6c42bf8ff
   ```

3. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 22.2 How to Remove This Feature from Your Build

In case you have tested this feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down provider_booking_email_note
   ```

2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert d42c8f335bd110f353d4fa3fd13ffcf6c42bf8ff
   ```


## 23. Language Replacements Support

This feature makes it possible to tweak the terminology used in the Easy!Appointments UI.

This is controlled with the **Language Replacements** setting under *Admin > Settings > General Settings*. This is a text field, containing a set of rules for replacing words in the language translations.

A rule is written as `string_to_be_replaced=string_to_replace_it_with`. Rules are separated with a semicolon (`;`). Only whole words are replaced, to avoid incorrect replacements where the string to be replaced happens to be inside another word with a totally different meaning. For example, `ape` can be changed to `monkey` but not when it's part of the word `paper`. The drawback with this is that even plural forms and compound words needs separate rules. For example, rules must be added for both `provider=tutor` and `providers=tutors`. The rules are case-insensitive, but the original casing is reapplied to the replaced word.

Exceptions can be added for cases where the rules should not be applied. These are prefixed with an exclamation mark (`!`). For example, to change `provider` to `tutor` but not when it appears in the technical context `identity provider`, you can write `provider=tutor;!identity provider;`.

Here's a complete set of rules for changing `provider` to `tutor` and `customer` to `student` both in English and Swedish. The absence of compound words in English makes the rules much more simple than those in Swedish:

```
provider=tutor;providers=tutors;customer=student;customers=students;utförare=handledare;utförarens=handledarens;utföraren=handledaren;utförarscheman=handledarscheman;utförarvalssidan=handledarvalssidan;kund=student;kunden=studenten;kunder=studenter;kunderna=studenterna;kunduppgifter=studentuppgifter;kundaviseringar=studentaviseringar;kundåtkomst=studentåtkomst;kundinloggning=studentinloggning;kunddata=studentdata;!identity provider;
```


### 23.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick ce5a3707655cd3b0af85186888c2ce8fd9a2f84d
   ```

3. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 23.2 How to Remove This Feature from Your Build

In case you have tested this feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down language_replacements
   ```

2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert ce5a3707655cd3b0af85186888c2ce8fd9a2f84d
   ```


## 24. Provider Daily Booking Limit

This feature makes it possible for each provider to set a personal maximum limit of bookings per day. When this limit is reached, the whole day will become unavailable for further bookings for that provider, even if there would be available times left in the calendar.

This makes it possible for providers e.g. to make a whole day available for bookings, while having a safeguard that they won't get more bookings per day than they can handle. This also gives a bit more flexibility for customers to book times that are suitable for them, if the provider does not mind how those bookings are distributed throughout the day.

This is controlled with the **Maximum number of appointments per day** setting, in the provider account settings, under the *Options* section. Leaving this as zero means that there is no maximum limit (other than the number of available timeslots in the calendar, as usual).

### 24.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick 77a39f04e329de61d2fde3b0450b0630b9249a1e
   ```
3. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 24.2 How to Remove This Feature from Your Build

In case you have tested this feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down provider_daily_booking_limit
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert 77a39f04e329de61d2fde3b0450b0630b9249a1e
   ```


## 25. Reply To Customer for Provider Email

This feature sets the customer's email as the Reply-To address for the booking email sent to the provider. This makes it possible for providers just to press reply and the email is then sent to the customer making the booking. No need to copy the email address from the message body.

This functionality can be enabled/disabled with the **Reply-To Customer in Provider Email** setting under *Admin > Settings > Booking Settings*.

### 25.1. How to Add This Feature to Your Build

To add this feature to your build, follow these steps:

1. *Do this once before adding any of the feature commits*: Do the steps in [Required Commands and Commits](#required-commands-and-commits). This will make sure that you have the *main* branch of the Lnu fork of Easy!Appointments available and some shared commits already installed. 

2. Add this feature to your build, use the following command in the root of your Easy!Appointments installation.
   ```bash
   git cherry-pick a846aa0807ece58ff8ba4f8bb0d9aa6f0768666c
   ```
3. Finally, migrate the database to include the new settings:
   ```bash
   php index.php console migrate
   ```

### 25.2 How to Remove This Feature from Your Build

In case you have tested this feature and decide that you don't want to have it after all, you can run the following commands in the root of your Easy!Appointments installation.

1. Roll back the database migration:
   ```bash
   php index.php console migrate_lnu_down reply_to_customer_for_provider_email
   ```
2. Revert the commit (another commit will be created, undoing the changes):
   ```bash
   git revert a846aa0807ece58ff8ba4f8bb0d9aa6f0768666c
   ```


## General Merge and Migration instructions

### Required commands and commits 

Before taking into use any of the feature commits, make sure you run the following commands. You only need to do this once.

First, make the *main* branch of the Lnu fork of Easy!Appointments visible in your local installation. This will make the feature commits described in this README available for cherry-picking:
```bash
git fetch https://github.com/toekaa-lnu/easyappointments.git main
```

Then, add commits that implement shared functionality and fixes for crucial bugs:

1. Simplified database migration for the Lnu commits. Earlier, any added migration files had to be renumbered to fit in with existing migration files, and they had to be added/removed in strict order. This is no longer necessary. Instead, you can just run the usual `php index.php console migrate` command after adding each Lnu commit. This will always run all installed Lnu migration files, and each file will make the database changes only if they have not already been made before, so it's always safe to re-run it. Individual Lnu commits can also be de-migrated with the command `php index console migrate_lnu_down <name>`, regardless of which order they were installed in. The name can be found in the "How to Remove this Feature from Your Build" subsection for each feature commit in this document.
   ```bash
   git cherry-pick 46033f817c2814f13ce166efdeeb26d340260ce1
   ```

2. Support for subsettings. Not all Lnu commits depend on this, but there's no harm adding it in any case.
   ```bash
   git cherry-pick 4541583172caf036556988cc8f0c2fa516c35eda
   ```

3. Removing logging for errors when a text does not exist in the translation files. This has no visible functionality, but the Lnu commits make use of the translation functionality and without this your logs will fill up quickly.
   ```bash
   git cherry-pick 4b29dc6be3d56eff67fd4db925d75183d7025ba2
   ```

4. A bugfix for null string handling in hkdf encryption. Without this fix the [Zoom](#20-zoom-meetings-integration) and [OIDC](#21-oidc-booking-login-integration) feature commits won't work, since they rely on this for encrypting the client secret in the database. This fix corrects a bug in CodeIgniter's own encryption library (`system/libraries/Encryption.php`), not in the Easy!Appointments application code.
   ```bash
   git cherry-pick a2c0aa5f178214daee88e3cde4d949e1fa1fc34b
   ```

5. Fixes for two major bugs in Working Plan Exceptions. A date mismatch bug caused a whole day being shown as available in the Calendar whenever a Working Plan Exception was added to it, and then navigating away and back to the calendar page. A second bug caused a new Working Plan Exception to be added to the same day, when editing an existing one. The [Availability marking]() feature commit depends on this fix to work at all, as it uses Working Plan Exceptions as the underlying functionality.
   ```bash
   git cherry-pick bbee8e38a055e8c0c81e28a8e87887733f78d8a4
   ```

6. Fix for a write permission error in HTML purifier. This caused an error in the UI whenever any texts from the *Legal Contents* settings was shown.
   ```bash
   git cherry-pick 2c1b72794704bf1045a9e7dde188336e68f82806
   ```

You can find additional commits in the git log, but most likely these are only of interest for us here at Lnu. However, feel free to use them as you wish.

### When Something goes Wrong

#### Case #1: Git cherry-pick fails with a warning that it is empty

This usually means that you already have all the changes included in the cherry-picked commit. The most likely situation is that you're cherry-picking the same commit twice by mistake. This can be solved by the following command:
```bash
git cherry-pick --skip
```

The warning might say something about conflict resolution, but it does not necessarily have anything to do with that. Skipping the cherry-pick is always a safe choice here.

#### Case #2: Git cherry-pick fails with a warning that there are conflicts

This is a more tricky situation. It means that there are existing code changes in your installation, and the cherry-picked commit tries to change exactly the same part of the code. It was not possible to resolve the problem automatically.

You have two choices:

* **Abandon the commit**. This discards the cherry-pick attempt entirely, as if you never tried to do it. This is safe to do, but then of course you can't take the commit into use.
  ```bash
  git cherry-pick --abort
  ```

* **Try to resolve the conflicts manually**. Open each conflicted file and look for the conflict markers (<<<<<<<, =======, >>>>>>>), edit the file to keep the correct content, then remove the markers. Once done, stage the resolved file(s) and continue:
  ```bash
  git add <files>
  git cherry-pick --continue
  ```
  This will open an editor to confirm the commit message - if you're not familiar with your editor's save-and-exit shortcut, this is a good moment to look it up before you're stuck in it.

#### Case #3: Rolling back a feature fails with "LNU migration not found"

This means the `application/migrations/lnu_<name>.php` file used by the following command doesn't exist:
```bash
php index.php console migrate_lnu_down <name>
```
This usually happens for one of two reasons:

* You made a typo in `<name>`. Double check it against the "How to Remove This Feature from Your Build" instructions for the feature you're trying to remove.
* You're trying to remove a feature that was never actually added (cherry-picked) to your build in the first place, so there's nothing to roll back.

#### Case #4: A feature doesn't seem to work after cherry-picking its commit

If a feature's code has been added but it doesn't behave as expected, for example a settings page shows an error, or a new setting doesn't seem to save, double check that you also ran the database migration:
```bash
php index.php console migrate
```
Cherry-picking a feature's commit only adds its code - the database changes it depends on are only applied once you migrate.

### General Disclaimer

This code is provided as-is. Use it at your own choice and at your own risk. Having said that, the code is used for us at Lnu in production for two different installations/teams and it's working fine for us. This code is published in order to let others benefit from the work we have done, just like we have benefitted from being able to use the original Easy!Appointments code.

You are free to take this code into use in your own build and improve and adapt it to your own purposes as you wish. Feel free to ask questions if you run into problems but be aware that we may not always be able to or have time to help.

Like the original Easy!Appointments, this code is licensed under [GPL v3.0](https://www.gnu.org/licenses/gpl-3.0.en.html) and content under [CC BY 3.0](https://creativecommons.org/licenses/by/3.0/).

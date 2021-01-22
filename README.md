# Translation Dictionaries Generator

[![Latest Stable Version](https://img.shields.io/packagist/v/blackbird/translation-dictionaries-generator.svg?style=flat-square)](https://packagist.org/packages/blackbird/translation-dictionaries-generator)
[![License: MIT](https://img.shields.io/github/license/blackbird-agency/magento-2-translation-dictionaries-generator.svg?style=flat-square)](./LICENSE)

This module allows you to generate translation dictionaries.
For each language on your website get the CSV file with default text and existing translations. 
Your translator can complete missing translations and modify existing ones using the generated CSV files.
Once they are completed with all the translations you only need to import them in the i18n folder of your Magento 2.

## Setup

### Get the package

**Composer Package:**


```
composer require blackbird/translation-dictionaries-generator
```

**Zip Package:**

Unzip the package in app/code/Blackbird/TranslationDictionariesGenerator, from the root of your Magento instance.


### Install the module

Go to your Magento root directory and run the following magento command:

```
php bin/magento setup:upgrade
```

**If you are in production mode, do not forget to recompile and redeploy the static resources, or use the `--keep-generated` option.**

### Administrators

First, you can exclude all modules that you don't want to be translated (ie: Back office modules) to reduce the number of translations.
In order to exclude modules go to **Stores** > **Configuration** > **Blackbird Extensions** > **Translation Dictionaries Generator**. 
Here you have a multiselect field with all the module selected for exclusion.

### Command

To start the translation dictionaries generation you have to run this command:
```
php bin/magento blackbird:translation:export
```
**Warning:** without parameters it will export all languages available on your website

You will find dictionaries files in the following folder: $ROOT_MAGENTO/var/translation_export.

#### Parameters

- ```-l``` or ```--locale_code``` allow you to define which language(s) to export like this:
```
php bin/magento blackbird:translation:export --locale_code=fr_FR,en_US
```

- ```-i``` or ```--input``` allow you to use an input CSV file containing all Magento's strings. 
It will generate a new file with the existing translations for each language for the given strings.
```
php bin/magento blackbird:translation:export -i /home/blackbird/phrases.csv
```

- You can combine both options:
```
php bin/magento blackbird:translation:export -i /home/blackbird/phrases.csv -l fr_FR,en_US
```

### Possible error

Our module uses a feature of Magento to collect all translatable strings of your website like the native command ```i18n:collect-phrases```

This feature can generate an error if it finds an empty translatable string (like ```__('')```):

```
In Phrase.php line 90:
                 
  Missed phrase
```

To handle this error there are 2 possibilities:

- Your IDE can search that easily
- Use ```grep``` command in your Magento root folder:
```
grep -rnw . -e "__('')"
```

As soon as you have identified the empty translatable string you can give it a value or comment on it. **But be careful** to rollback after the export (use it in local for more security)

## Support

- If you have any issue with this code, feel free to [open an issue](https://github.com/blackbird-agency/magento-2-translation-dictionaries-generator/issues/new).
- If you want to contribute to this project, feel free to [create a pull request](https://github.com/blackbird-agency/magento-2-translation-dictionaries-generator/compare).

## Contact

For further information, contact us:

- by email: hello@bird.eu
- or by form: [https://black.bird.eu/en/contacts/](https://black.bird.eu/contacts/)

## Authors

- **Kevin Weyhaupt** - *Maintainer* - [It's me!](https://github.com/kevin-blackbird)
- **Blackbird Team** - *Contributor* - [They're awesome!](https://github.com/blackbird-agency)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

***That's all folks!***

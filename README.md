# fr_FR
## AbandonedCart

* Ce module vous permet d’envoyer un courrier électronique après un délai défini pour rappeler aux clients qu’ils ont des articles dans leurs paniers.

## Installation

### Manuellement

* Copiez ce module directement dans votre répertoire ```<thelia_root>/local/modules/``` et verifier que le nom du module soit AbandonedCart
* Activez le dans votre back office Thelia

### Composer

Ajoutez cette ligne à votre fichier composer.json au coeur de votre Thélia

```
composer require your-vendor/abandoned-cart-reminder-module:~1.0
```

## Usage

* Directement depuis votre back office, vous pouvez programmer un temps pour envoyer les emails à vos clients. Vous devez également programmer un cron
Dans un terminal, tapez :
```
crontab -e
```
Et ajoutez cette ligne à la fin de votre fichier pour effectuer une vérification toutes les minutes:
```
* * * * * /path/to/php /path/to/Theliadirectory/Thelia examine-abandoned-carts >> /path/to/thelia/log/abandonedcarts.log 2>&1
```
Sauvegardez le.

# en_US
## AbandonedCart

* This module allows you to send an email after a defined time to remember customers that they have items in their carts.

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is AbandonedCart.
* Activate it in your thelia administration panel

### Composer

Add it in your main thelia composer.json file

```
composer require your-vendor/abandoned-cart-reminder-module:~1.0
```

## Usage

* Directly in your back office, you can set a timer to send the email to the customer. You also have to set up a cron.
In a terminal, type :
``` 
crontab -e
```
and add this line a the end of your crontab file to execute a verification every minute:
```
* * * * * /path/to/php /path/to/Theliadirectory/Thelia examine-abandoned-carts >> /path/to/thelia/log/abandonedcarts.log 2>&1
```
Save it.
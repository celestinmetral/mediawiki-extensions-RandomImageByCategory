# RandomImageByCategory

This repository is a personal fork of the Mediawiki extension of the same name, [RandomImageByCategory](https://www.mediawiki.org/wiki/Extension:RandomImageByCategory) . I’ve wanted more flexibility in the output, and as the original extension is outputing
 image in basic HTML tags `<a></a>`, I prefered to output it in Mediawiki’s `[[File:]]` tags. The modifications I’ve done are :

- switched from a normal extension to a [parser fonction](https://www.mediawiki.org/wiki/Manual:Parser_functions)
- edited the output to print directly a `[[File:]]` tag.

Also, as what I really wanted was a random image of the day, I’ve edited the random selection part :

-  get the current day number
-  limit the query to this amount of line
-  get the image index " this number "

I used a modulus operator if there is less images than the date number.

**Warning** : As my wiki is in french, you have to switch some key words in english : the key 'fr' in RandomImageByCategory.i18n.php to 'en', the tag `[[Fichier:]]` to `[[File:]]` and all its options in english.

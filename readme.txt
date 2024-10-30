=== In Image Ads Manager ===
Contributors: stephan.l
Donate link: http://www.smart-webentwicklung.de/wordpress-plugins/in-image-ads-manager/
Tags: ads, in-image-ads, textads, image
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

EN: Display text ads as image overlay when hovering over an image. DE: Erstelle Text-Ads, die beim Hovern über ein Bild angezeigt werden.

== Description ==

= EN: =

Create and display clickable text ads for your images. An ad will be displayed when a visitor of your blog hovers over a specific image.

After creation of a new ad you get an unique id for it. This id you use as class name for the image that shall be overlayed by the ad.

For example: `<img class="iiam-1" src="{image-path}" width="400" height="200" />`

A live preview you found here: [Plugin-Website](http://www.smart-webentwicklung.de/wordpress-plugins/in-image-ads-manager/ "Live-Preview: In Image Ads Manager")

= DE: =

Mit dem In Image Ads Manager könnt ihr einfache Textlink-Ads erstellen, die dann euren Besuchern beim Hovern über ein Bild angezeigt werden.

Für eine ausführliche Beschreibung und Live-Vorschau siehe: [Offizielle Plugin-Website](http://www.smart-webentwicklung.de/wordpress-plugins/in-image-ads-manager/ "Dokumentation: In Image Ads Manager")


= Dokumentation =
* [Offizielle Plugin-Website](http://www.smart-webentwicklung.de/wordpress-plugins/in-image-ads-manager/ "Dokumentation: In Image Ads Manager")

= Author =
* [Blog](http://www.smart-webentwicklung.de/ "Blog: Smart-Webentwicklung")
* [Plugins](http://www.smart-webentwicklung.de/wordpress-plugins "Plugins")
* [Twitter](http://twitter.com/smartweb89 "Twitter-Profil")

== Installation ==

= EN: =

1. Upload the downloaded directory `in-image-ads-manager` in your `/wp-content/plugins/` directory or dowload and install the plugin over your plugin manager of WordPress.
2. Activate the plugin over the plugin manager of WordPress.
3. There is now a new main menu called `In Image Ads`, where you can create new ads.
4. In order to activate an ad for a specific image you have to use the generated ad id as class name of the image. (e.g. `<img class="iiam-1" src="{image-path}" width="400" height="200" />`)


= DE: =

1. Lade das heruntergeladene Verzeichnis `in-image-ads-amanger` in dein `/wp-content/plugins/` Vezeichnis hoch oder lade und installiere das Plugin über deinen Plugin-Manager von WordPress.
2. Aktiviere das Plugin über den Plugin-Manager von WordPress.
3. Es gibt nun einen neues Hauptmenü namens `In Image Ads`, wo du die neue Ads erstellen kannst.
4. Um ein Ad für ein bestimmtes Bild zu aktivieren musst du die generierte Ad-ID als Klassenname für das Bild verwenden. (z.B. `<img class="iiam-1" src="{image-path}" width="400" height="200" />`)

== Frequently Asked Questions ==

= EN: Can I use one ad for several images that are on the same page? DE: Kann ich ein Ad für mehrere Bilder verwenden, die auf der gleichen Seite sind? =

EN: Yes, that is possible.
DE: Ja, das geht.

= EN: Is it possible to use HTML for the title or text input? DE: Kann ich HTML für die Titel- oder Text-Eingabe verwenden? =

EN: No, that is not possible.
DE: Nein, das geht nicht.

= EN: How can I immediately deactivate an ad that is activated for several images without the effort to removing every single class name from the related images? DE: Wie kann ich ein Ad sofort deaktivieren ohne extra alle Klassennamen der betreffendes Bilder zu löschen? =

EN: You just need to throw the ad in the trash. When an ad is in the trash it will not be considered and displayed in the frontend anymore.
DE: Du musst das Ad nur in den Papierkorb werfen. Ads die sich im Papierkorb befinden, werden im Frontend nicht berücksichtig und angezeigt.

== Screenshots ==

1. Example for an in image ad
2. Overview (admin)
3. Style settings with live preview (admin)

== Changelog ==

= 1.1 =
* Replaced deprecated Colorpicker Farbtastic with new default WordPress Colorpicker Iris [Details](http://core.trac.wordpress.org/ticket/21206)
* Support for WordPress 3.5.1 ensured

= 1.0 =
* EN: Release DE: Veröffentlichung
=== TFD Word of the Day ===
Contributors: dpchiesa
Donate link: http://dinochiesa.github.io/TfdWotdWidgetDonate.html
Tags: Dictionary, widget, words, TheFreeDictionary.com, wotd
Requires at least: 3.2
Tested up to: 3.9.1
Stable tag: 2014.07.03
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

== Description ==

This is a Wordpress Widget that displays an English Word-of-the-Day. It
provides a word, along with a definition and an example usage.

The actual word is provided by TheFreeDictionary.com to your wordpress blog.
TFD refers to TheFreeDictionary.com .

== Installation ==

1. Download tfd-wotd-wp-plugin.zip and unzip into the
  `/wp-content/plugins/` directory

2. From the Wordpress admin backend, Activate the plugin through the
   'Plugins' menu

3. From the Wordpress admin backend, in the Widgets menu, drag-and-drop
   the widget to a widget container, such as your page sidebar. You can
   place this widget in any position you like.

4. Optionally, in the settings UI, specify the Title for the widget.  By
   default it is simply "Word of the Day".

That's it !


== Frequently Asked Questions ==

= Why would anyone use this plugin? =

If you want to dress up your wordpress blog with a Word-of-the-Day widget, you'd use this plugin.

= What is a "word of the Day"? =

Someone who runs TheFreeDictionary.com chooses a special word for each
day, and provides the definition and a sample usage of that word.

= How does this plugin really work? =

TheFreeDictionary.com provides some code that webmasters can add to
websites, in order to publish the word of the day on their sites. This
plugin just adapts that code for simple use within wordpress.

Warning: geek-speak ahead.

The plugin uses curl on the server side to retrieve the word-of-the-day.
It caches the result, then embeds it into the generate page. On
subsequent page requests, the plugin uses the cached result. The cache
life is 120 minutes.

= Where is the cache stored? =

The content is cached in a file in a "cache" subdirectory of the wp-content directory.
You probably don't need to concern yourself with the cache.

= How can I change the styling of the widget? =

There is a wotd.css file included in the widget. You can make styling
changes there. Any changes you make there will be overwritten if you
download and install a newer version of the widget.

You can also style the widget elements with your own stylesheet.  To do
this you need to know CSS and also how the widget is rendered.

The widget is normally rendered in a widget container, which is styled
with these css classes: widget-container widget_tfdwotd .  Therefore in
a css sheet you might use something like this:

.widget-container.widget_tfdwotd { .... }

Within that container, the structure of the rendered HTML is:

  &lt;h3&gt; widget Title &lt;/h3&gt;
  &lt;div class='tfd-wotd'&gt;
    &lt;h3&gt;word&lt;/h3&gt;
    &lt;table&gt; ... &lt;/table&gt;
  &lt;/div&gt;

Therefore, you may wish to use these CSS selectors:

    .widget-container.widget_tfdwotd div.tfd-wotd { ... }
    .widget-container.widget_tfdwotd div.tfd-wotd > table { ... }
    .widget-container.widget_tfdwotd div.tfd-wotd > table td { ... }
    .widget-container.widget_tfdwotd { ... }


= Do I need to register with TheFreeDictionary.com? =

No.


== Screenshots ==

1. This shows the rendering of the Widget in the sidebar of a WP blog.
2. This shows how to activate tfd-wotd in the Plugins menu in the WP Admin backend
3. Configuring the settings for the WOTD widget in the WP Admin backend.


== Changelog ==

= 2012.07.17 =
* tested on wordpress v3.9.1

= 2012.07.17 =
* initial implementation.

== Dependencies ==

- Your Wordpress host must allow outgoing http connections. (true in most cases)
- Wordpress must be configured to enable curl. (true in most cases)


== Thanks ==

Thanks for your interest!

You can make a donation at http://dinochiesa.github.io/TfdWotdWidgetDonate.html

Check out all my plugins:
http://wordpress.org/extend/plugins/search.php?q=dpchiesa


-Dino Chiesa


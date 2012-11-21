=== NextGEN Gallery Voting ===
Contributors: shauno
Donate link: http://shauno.co.za/donate/
Tags: nextgen-gallery, nextgen, gallery, voting, rating, ratings, nextgen-gallery-voting
Requires at least: 2.9.1
Tested up to: 3.4.2
Stable tag: 2.2

This plugin adds the ability for users to vote on NextGEN Galleries and Images. A few basic options give the ability to limit who can vote on what.

== Description ==

This plugin adds the ability for users to vote on NextGEN Galleries and Images. You can choose between star ratings, a drop down rating between 1-10, and a like/dislike (thumbs up/thumbs down) voting types.
You can limit if a user needs to logged in to vote, if they can see results, and how many times they can vote on specific images.

NGG Voting was inspired by a request from [Troy Schlegel of Schlegel Photography](http://www.schlegelphotography.com/).  Please read the FAQ for more info on how it works.

Please read the FAQ, as you are required to add a tag to templates for certain functionality to work!

**QUESTIONS ASKED ON THE FORUM THAT ARE IN THE FAQ WILL NOT BE ANSWERED BY ME**

== Frequently Asked Questions ==

= How do I make the voting form show on my site? =
For galleries it's easy.  You just need to enable voting on the gallery, and the voting form will automatically be appended after the gallery.
For images there's an extra step.  First you need to enable voing on the specific images you want.  Then you need to add a tag to the gallery template from NextGEN Gallery.  I'm going to use NextGEN version 1.9.6 to as the example, but it should be pretty much the same for all versions:
Between line 38 and line 53 in `/nextgen-gallery/view/gallery.php` is the loop that shows each image in a specific gallery.  You need to add the following tag in that loop: `<?php echo nggv_imageVoteForm($image->pid); ?>`.
That will output the vote form where you put it.  Personally I like to place it on a new line after the close `<a>` tag (new line created 45)
PLEASE NOTE: Do not put the tag inside the `<a>` tag that wraps the image in some of the templates, or the link will conflict with the voting process.
PLEASE NOTE 2: The `/nextgen-gallery/view/gallery.php` template is used with the shortcode `[nggallery id=x]`. If you change the shortcode or specify a different template, you need to add the tag to the template you specify.

= Can I make the voting look more like my theme =
This plugin intentionally adds very little styling to the voting forms. It does provide plenty of classes and ids though, allowing you to style it to fit in with your site.

= I updated NextGEN Gallery, and voting has disappeared! =
When you update NextGEN Gallery, it overwrites the templates in its `/view/` directory. This means you need to re-add the tag. If you want to prevent the overwriting in the future, you can move the templates to `wp-content/themes/[YOUR-THEME]/nggallery/`.

= How do I see the voting results =
Under the Gallery or Image options, the current average vote shows along with how many votes have been cast.  Click on the number of votes cast to show more info on those votes.

= Known issues =
* If you put the image voting tag (`<?php echo nggv_imageVoteForm($image->pid); ?>`) inside the `<a>` wrapping them image in a template, you are going to experience problems. This is because the voting process uses its own `<a>` tags too.
* If you have the 'Show ImageBrowser' setting under `Gallery->Options->Gallery` ticked, it will conflict with the voting process. This is because both ImageBrowser, and the voting use the GET variable `pid`. I will change this in a future release for better compatibilty with ImageBrowser.

== Installation ==

1. Unzip the plugin to your `wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to 'Manage Gallery' in NextGEN, and select a gallery to see the new options
1. Remember to add the tag to the gallery template for image voting to work!

== Changelog ==

= 2.2 =
* Changed the AJAX JavaScript to play nicely with themes running WordPress' auto formatting.
* Fixed a bunch of PHP notices.

= 2.1 =
* Rewrote voting functions to allow for better future compatibility.

= 2.0 =
* Massive rewrite, with many under-the-hood changes, but very few visible. This rewrite is to make adding features easier in the future.
* Update default options screen to use new WP styling.
* Updated gallery voting options to not need javascript to load and save.
* Changed order of voting options so star is out-the-box default (star, drop down, like/dislike).
* Fixed bug including CSS more than once on occations.
* Improved voting results in dashboard, to not always reflect out of 10.

= 1.10.1 =
* Fixed a bug that saved a vote for all galleries on the page if there was more than one (thanks oxiw for the report and Torteg for the confirmation example)

= 1.10 =
* Fixed admin viewing details of votes cast
* Made drop down voting jump down to last image voted on (simple anchor).
* Added 'Clear Votes' button to images.

= 1.9.3 =
* Added honey pot spam protection for drop down voting. Not perfect, but it's a start.

= 1.9.2 =
* Changed the HTML comments used as markers for JS output when AJAX voting to not be so similar to Apache SSIs.

= 1.9.1 =
* Fixed a bug that contiuned to show voting form even if option to not show results was selected (It didn't reveal results, just was a confusing interaction, thanks to Iced_Plum for reporting it)

= 1.9.0 =
* Added ability to allow only 1 image vote per gallery

= 1.8.2 =
* Removed 'Gallery' filter from top images report as it was buggy

= 1.8.1 =
* Made compatible with NGG 1.8.0 (Thanks to csillery for reporting the issue)

= 1.8 =
* Fixed a bug stopping votes saving if MySQL was in STRICT MODE
* Fixed a bug showing floating numbers for low rated images
* Added report to list top images

= 1.7.2 =
* Added 'Voting Type' default when creating a new gallery

= 1.7.1 =
* Fixed a bug stopping voting working when including the gallery with the [nggtags] shortcode (Thanks migf1 for finding and reporting it)

= 1.7 =
* Made 'like/dislike' and 'star' ratings use ajax to cast votes.  But it will fall back if javascripti is not enabled

= 1.6.2 =
* I screwed up the backwards compatibility, sorry.  Use 1.5 for < NGG1.7

= 1.6.1 =
* Made it backwards compatible with NGG 1.6.x and lower. Should have been done with the last update, but I was spaced on pain meds

= 1.6 =
* Made this plugin compatibile with NextGEN Galley 1.7.x and greater, which breaks compatibility with lower versions of NGG

= 1.5 =
* Added a new type of voting, the "Like / Dislike"

= 1.4 =
* Added the ability to set default voting options for new Galleries and Images

= 1.3.1 =
* Fixed a broken close label tag that caused some issues with the drop down voting (Thanks to Mae Paulino for pointing it out)

= 1.3 =
* Fixed a bug that directed users to a 404 if using star ratings with pretty URLs enabled

= 1.2 =
* Added the ability to choose to vote using 5 star rating
* Removed hook that was creating a blank admin menu item in the Wordpress backend

= 1.1 =
* Added voting to images
* Fixed bug that broke the admin layout in Internet Explorer

= 1.0 =
* Initial Release

== Upgrade Notice ==

= 2.2 =
This update makes AJAX voting play nicely with themes that use WordPress' auto formatting.

= 2.1 =
Updates to voting engine for future compatibility.

= 2.0 =
Version 2.0 is a big restructure of the plugin. If you have made customizations or are calling APIs externally, please test thoroughly before updating your live site!


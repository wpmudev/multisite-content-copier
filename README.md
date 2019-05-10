# Multisite Content Copier


**INACTIVE NOTICE: This plugin is unsupported by WPMUDEV, we've published it here for those technical types who might want to fork and maintain it for their needs.**

## Translations

Translation files can be found at https://github.com/wpmudev/translations


## Multisite Content Copier is the perfect solution for copying posts, pages, users and even plugins to other sites on your multisite network.

![wizard-copy-735x470](https://premium.wpmudev.org/wp-content/uploads/2013/11/wizard-copy-735x470-583x373.jpg)

 Follow the simple guide for quick copying.

### A Big Time Saver

Save a bunch of time on site creation and setup. Populate entire sites in a matter of seconds. Quickly seed new sites with content, users and plugins. Move content to a single site, groups of sites or every site on your network with a click.

### Keep It Simple

Start copying content out-of-the-box without any configuration. The included wizard guide makes it easy to begin sharing content across your network immediately. Pages, posts, custom post types, users and plugins can all be copied to new and existing sites. 

### Share to Groups

Use the site groups feature to share content across a group of sites simultaneously. You can have as many Site Groups as you wish, providing ultimate flexibility.

![groups-735x470](https://premium.wpmudev.org/wp-content/uploads/2013/11/groups-735x4701-583x373.jpg)

 Share content to targeted site groups.

 

![blog-templates-735x470](https://premium.wpmudev.org/wp-content/uploads/2013/11/blog-templates-735x470-583x373.jpg)

 Integrate with New Blog Templates.

### Template Integration

Supercharge new site creation and network management with built-in New Blog Templates integration. Apply content to every site created in a specific template group to completely automate site creation.

 

### Quick Share

Create new posts and quickly share the content to any site on your network directly from the post editor. Simply publish the post and then select where you would like to copy it to. Perfect for adding news items to multiple sites in your network.

![post-copy-735x470](https://premium.wpmudev.org/wp-content/uploads/2013/11/post-copy-735x470-583x373.jpg)

 Share directly from the post editor.


## Usage

### To Get Started

Start by reading [Installing plugins](../wpmu-manual/installing-regular-plugins-on-wpmu/) section in our comprehensive [WordPress and WordPress Multisite Manual](https://premium.wpmudev.org/manuals/) if you are new to WordPress.

### Configuring the Settings

Once installed and network-activated, you'll see a new menu item in your network admin: Content Copier. 

![Multisite Content Copier- Menu](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1000-menu.png)

 The first thing you'll want to do is visit the Settings page. Click the _Settings_ sub-menu item now. The only setting you'll see there at this time is a checkbox to enable integration with the [New Blog Templates](https://premium.wpmudev.org/project/new-blog-template/ "WordPress New Blog Templates Plugin - WPMU DEV") plugin. 

![Multisite Content Copier - Settings](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1000-settings.png)

 What does that do, you ask? Well, if you have the New Blog Templates plugin installed on your site, activating this feature will enable you to copy content only to sites using specific templates. Cool huh?

*   Important: content can only be copied to template blogs created after Multisite Content Copier was installed.

Already have New Blog Templates installed? Great, then check that box now to activate the integration. Now let's take a look at the _Sites Groups_ settings. Click that now. Under the first tab, _Groups_, you'll see a layout very similar to your blog categories. 

![Multisite Content Copier - Groups](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1000-groups.png)

 This is where you can create groups of sites that you can target and copy content to. Go ahead and create a group now to help you get familiar with how this plugin works. Don't worry, you can edit or delete the group later. Now click the _Sites_ tab at the top of your screen. 

![Multisite Content Copier - Sites](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1000-sites.png)

 Here, you'll see a list of all the sites in your network, and which site groups they are assigned to. To assign any site to a group (like the one you just created), check the box next to the site's name. Then select a group from the dropdown menu above, and click "Assign to Group". To remove a site from a group, check the box next to its name. Then select the group from the menu, and click "Remove from Group". You can add sites to multiple groups simply by repeating this process for each group. Next, click the _New Blog Templates_ tab at the top of the screen. Note that this tab will only appear if you have activated the integration under the Settings tab. 

![Multisite Content Copier - Templates](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1000-templates.png)

 There are no settings under this tab as it is just there for convenience so you can see, at a glance, how many sites in your network are using each template.

### Copying Content

Now click the _Content Copier_ menu item. This is where the fun really begins! On the 1st screen, you can choose what content type you want to copy from the source site that you'll select in the next step. 

![Multisite Content Copier - Copy Content](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1000-copy-content2.png)

 1\. Copy pages, posts or custom post types.  
2\. Copy users to other sites.  
3\. Activate multiple plugins on other sites.

 1\. To copy content, you must first select the content type.

*   Check _Pages_ to select from all the pages on the source site. This includes published pages, as well as those in draft and pending status.
*   Check _Posts_ to select from all the posts (published, draft & pending).
*   Select _Custom Post Type_ to specify which post type to copy in the next step (published, draft & pending).

2\. Yes, you can also copy _Users_ from the source site to other sites in your network with this plugin! Note that their roles & capabilities will be copied also. 3\. _Activate plugins_ enables you to activate multiple plugins on all the destination sites you select in the next step. Note that plugins that are network-activated will not be available (they're already active). Let's go through the process with the "Pages" content type to help you get familiar with how it all works. Select "Pages", then click Next Step. 

![Multisite Content Copier - Select Source](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1000-select-source1.png)

 Here is where you select the site from which your selected content type will be copied. You can either enter the site ID, or start typing the site path in the search form. That will return all matching results as you type to make your selection really easy. Once you've selected the source site, click Next Step. On this screen, you can select exactly which pages to copy from the source site. You also have a few _Additional Options_ to enable if you wish. 

![Multisite Content Copier - Select Items](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1000-select-content1.png)

 1\. Copy images to destination sites.  
2\. Set publish date to current date.  
3\. Copy parents also.  
4\. Even copy comments.  
5\. Select the pages to copy.

 1\. Check _Copy images_ to copy all images from the selected page(s) to the destination site(s) that you'll select at the next step.

*   This also adds the attached images to the media library of the destination site(s).
*   Note however that gallery shortcodes on pages of the source site will be copied as they are, so you may want to update those manually on destination site(s).

2\. Check _Update page created date_ to set the date created on the destination site(s) to the date the content is copied. 3\. _Copy parents_ will also copy over the parents of all selected items.

*   In our example, the "My Listings" page is a child of the "Listings" page. So if this option is selected, and we only select the "My Listings" child page, both child and parent will be copied.

4\. _Copy comments_ will copy all comments from the source page(s) to the one(s) copied to the destination site(s). 5\. Once you have selected the Additional Options you prefer, select the page(s) you want to copy in the page list on the right. Then click the "Add items to the list" button. 

![Multisite Content Copier - Content Add](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1330-content-add.png)

 Let's go ahead and select all of the above options to see how they work. If you are following along here on your own network, please be sure you have a page created with at least one image and one comment. Now click Next Step. This is where you select the site(s) you want to copy the content to. 

![Multisite Content Copier - Select Destination](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1000-select-destination1.png)

 1\. Copy to all sites in the network.  
2\. Select site(s) to copy to.  
3\. Copy to site groups.  
4\. Copy to blog template groups.

 To select your destination sites, you have 4 options to choose from: 1\. _All Sites_ will copy your selected content to all other sites in your network, including the main site. 2\. _Single Site_ enables you to add specific sites to a list.

*   First enter the site ID, or search just like you did for the source site.
*   Then click Add Site to add it to the list.

3\. _Site Group_ enables you to copy your selected content to all sites in a group that you created.

*   All site groups you create will appear in this list.
*   You can only copy to one group at a time.

4\. _Select by Blog Templates_ groups enables you to copy your selected content to all sites created using one of your blog templates.

*   Here again, you can only copy to one group at a time.
*   Please also remember that MultiSite Content Copier can only copy to template blogs that are created after it is installed.

Let's select a specific site for this walkthrough. 

![Multisite Content Copier - Select Destination 2](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1000-select-destination-2.png)

*   Enter the ID or path to a site you want to copy your page(s) to. You'll see the radio button is selected automatically.
*   Click "Add Site" to add it to the list that will appear.
*   Click Next Step.

Guess what? You're done. Now visit the destination site you had selected earlier. You should see your new page has been copied over in its current status, with all attachments & comments, and with the current date. Cool huh?

### Copying Custom Post Types

There is one additional option to select if you have chosen to copy entries from a custom post type on the source site. 

![Multisite Content Copier - Post Types](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1330-post-types.png)

 Once you have selected the source site in Step 2, click the Refresh post types button. That will list all the custom post types available on the source site. Select the one you wish to copy. If you don't see the custom post type you want in the list, you can manually enter the post-type slug in the field provided. On the next step, you can select the specific entries from that post type to copy to the destination site(s). You'll also be able to select to copy the terms (like tags, categories, etc) of you custom post-type. 

![Multisite Content Copier Copy Terms](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1330-copy-terms.png)

 When copying any post-type, including regular ol' posts, you can also filter the post list to show only posts from a selected category or taxonomy. Very handy when you have a huge list of posts to sift through. 

![Multisite Content Copier Taxonomy Filter](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1330-taxonomy-filter.png)

### Copying from the Post Editor

Oh yes, you can! On the post editing screen of every post type on every site in your network, the network admin has access to this new metabox: 

![Multisite Content Copier - Post Metabox](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1000-post-metabox.png)

 That means that at any time, the network admin can copy any post, page or custom post type on any site in the network, right from the post editor!

*   Note that from this metabox, you can choose to copy to all sites, or a group of sites. Copying to individually specified sites can only be done in the network admin.

So if you find some great content in your network that you want to post to other sites, no problem... click and it's done. You're welcome. :)

### Copying Users

Once you have selected the source site, you can select to copy all users, or only specific ones. 

![Multisite Content Copier - Copy Users](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1330-copy-users.png)

 Selecting users works just like selecting pages. Check the box next to each user you want to copy to the destination site(s). Then select "Add to the list" from the "Bulk Actions" menu, and click "Apply".

### Activating Plugins

Activating plugins is a little different than copying other post types. This feature will skip the source site step altogether, and go straight to Step 3 where you can select which plugins to activate on the destination site(s). 

![Multisite Content Copier - Activate Plugins](https://premium.wpmudev.org/wp-content/uploads/2013/11/multisite-content-copier-1000-activate-plugins1.png)

 Simply check the box next to each plugin you want to activate on the destination site(s). Then click Next Step to select the destination site(s).

*   Note that plugins designed to be network-activated only, or those that are already network activated are not available for selection in the list.

=== Plugin Name ===
Contributors: firimar
Tags: import, csv, posts, data
Requires at least: 3.0.1
Tested up to: 4.6
Stable tag: 0.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=65AE4A3BTR6FE

Provides ability to import any type of data from large CSV files into Wordpress

== Description ==

This simple plugin allows you to import any type of data from large CSV files into your wordpress installation.

Plugin reads CSV file and performs import of its records one by one through AJAX requests, so there are no server timeout issues.

You only need to provide PHP function to perform import of CSV field values into post or taxonomy term or whatever you need.

Usage example:

For example, assume we want to import posts from such CSV file:
`title,text,brand,image
"Check this","Samsung content",Samsung,"https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Samsung_Logo.svg/2000px-Samsung_Logo.svg.png"
"Some news","News<br>Here are some news",Philips,"http://www.underconsideration.com/brandnew/archives/philips_2013_logo_detail.png"`

Here we have post title, post content, custom field Brand and thumbnail image.

We can use this code in theme's functions.php file to implement import function:

`add_action('lcih_import_csv_row', 'lcih_import_csv_row');
function lcih_import_csv_row($row)
{
 $post_data = array(
     'post_type' => 'post',
     'post_status' => 'publish',
     'post_title' => $row['title'],
     'post_content' => $row['text']
 );
 $post_id = wp_insert_post($post_data);
 if (!$post_id)
 {
     echo "Error inserting post.";
     return;
 }

 update_post_meta($post_id, 'brand', $row['brand']);

 if ($row['image'])
     LargeCSVImportHandlerPlugin::download_post_thumbnail($post_id, $row['image']);

 echo "Created post ".$post_id;
}`

After that we can go to plugin admin page, select CSV file to upload and click "Start import" button. Import process will begin.


== Installation ==

Use the automatic installer from within the WordPress admin, or:

1. Download the .zip file by clicking on the Download button on the right
2. Unzip the file
3. Upload the files to your plugins directory
4. Go to the Plugins page from within the WordPress administration
5. Click Activate for Large CSV Import Handler
6. After activation a new Large CSV Import Handler menu item will appear in admin area.
7. Visit the Settings page to adjust values as you need.

You can now start using the plugin.

== Screenshots ==

1. Admin import page
2. Admin settings page
<?php

/**
 * @package Wimtorq
 */
/*
Plugin Name: Wimtorq 
Plugin URI: https://www.linkedin.com/in/amadou-sow-b4a14212b/
Description: a WordPress plugin called Wimtorq.
The plug-in should add an admin menu to the WordPress dashboard called Wimtorq.
The menu option once clicked should redirect to a landing page with two input text fields.
The text input will take and save the Stripe Api client secret and client id into the database as string variables and can be retrieved using Wordpress get_option(). The client id and secret will also be preloaded into the text fields from the database on page reload.
Create a shortcode called [wimtorq-stripe], where ever this shortcode is used, a <table> will be rendered using jQuery datatable(https://datatables.net/examples/data_sources/ajax) and an ajax request would be made backend to retrive and render the price name and amount
Using the https://github.com/stripe/stripe-php composer package, all prices belonging to the stripe account which owns the stripe api client and secret will be retrieved and returned as a JSON response to the ajax request on step 5 containing the price name and amount.
The datatable in step 5 should easily be paginated and the code to this plugin should be hosted on a public git repository and shared with us.
Version: 1.0.0
Author: Amadou Sow
Author URI: https://www.linkedin.com/in/amadou-sow-b4a14212b/
License: GPLv2 or later
Text Domain: Wimtorq
*/


//create menu
add_action('admin_menu', 'wimtorq');
function wimtorq()
{
    add_menu_page('Wimtorq', 'Wimtorq', 'manage_options', 'wimtorq', 'wimtorq_options');
}

//create the landing page
function wimtorq_options()
{
    $options = get_option('wimtorq_options');
?>
    <div class="wrap">
        <h1>Wimtorq</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wimtorq_options'); ?>
            <?php do_settings_sections('wimtorq'); ?>

            <p>
                <input type="submit" class="button button-primary" value="<?php _e('Save Changes') ?>" />
            </p>
        </form>
        <div class="lol">
            <p>ShortCode: [wimtorq-stripe]</p>
        </div>
    </div>
<?php
}

//create the settings
add_action('admin_init', 'wimtorq_admin_init');

//create the settings
function wimtorq_admin_init()
{
    register_setting('wimtorq_options', 'wimtorq_options');
    add_settings_section('wimtorq_main', 'Main Settings', 'wimtorq_section_text', 'wimtorq');
    add_settings_field('wimtorq_client_id', 'Client ID', 'wimtorq_setting_client_id', 'wimtorq', 'wimtorq_main');
    add_settings_field('wimtorq_client_secret', 'Client Secret', 'wimtorq_setting_client_secret', 'wimtorq', 'wimtorq_main');
}

//create the section text
function wimtorq_section_text()
{
    echo '<p>Enter your Stripe API credentials.</p>';
}

//create the client id setting
function wimtorq_setting_client_id()
{
    $options = get_option('wimtorq_options');
    echo "<input id='wimtorq_client_id' value='$options[wimtorq_client_id]' name='wimtorq_options[wimtorq_client_id]' size='40'/>";
}

//create the client secret setting
function wimtorq_setting_client_secret()
{
    $options = get_option('wimtorq_options');
    echo "<input id='wimtorq_client_secret' value='$options[wimtorq_client_secret]' name='wimtorq_options[wimtorq_client_secret]' size='40'/>";
}

//Create a shortcode called [wimtorq-stripe], where ever this shortcode is used, 
//a <table> will be rendered using jQuery datatable(https://datatables.net/examples/data_sources/ajax) 
//and an ajax request would be made backend to retrive and render the price name and amount.
add_shortcode('wimtorq-stripe', 'wimtorq_stripe');
function wimtorq_stripe()
{

    $options = get_option('wimtorq_options');
    $client_id = $options['wimtorq_client_id'];
    $client_secret = $options['wimtorq_client_secret'];
    $url = 'https://api.stripe.com/v1/prices';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $client_secret
    ));

    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response, true);
    $prices = $response['data'];
    $prices_array = array();
    foreach ($prices as $price) {
        $prices_array[] = array(
            'name' => $price['nickname'],
            'amount' => $price['unit_amount']
        );
    }


    echo '<table id="wimtorq-stripe-table" class="display" style="width:100%">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Name</th>';
    echo '<th>Amount</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($prices_array as $price) {
        echo '<tr>';
        echo '<td>' . $price['name'] . '</td>';
        echo '<td>' . $price['amount'] . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    wp_enqueue_style('datatable', 'https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css');
    echo '
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript">$(document).ready(function() { $("#wimtorq-stripe-table").dataTable( ); } );</script>
';
}

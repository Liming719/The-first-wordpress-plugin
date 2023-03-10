<?php
/*
Plugin Name: Word Count Plugin
Description: The very first plugin
Version: 1.0
Author: Li Ming
Text Domain: wcpdomain
Domain Path: /lang
*/

if(!defined('ABSPATH')) exit;


$checkboxArray = [
    [
        'label'=>esc_html__('Word Count', 'wcpdomain'),
        'db_name'=>'wcp_wordcount',
        'value'=>'1',
        'func'=>'CountingWord'
    ],
    [
        'label'=>esc_html__('Character Count', 'wcpdomain'),
        'db_name'=>'wcp_charcount',
        'value'=>'1',
        'func'=>'CountingCharacter'
    ],
    [
        'label'=>esc_html__('Read Time', 'wcpdomain'),
        'db_name'=>'wcp_readtime',
        'value'=>'1',
        'func'=>'estimateReadingTime'
    ]
];
class WordCountAndTimePlugin{
    
    function __construct(){        
        add_action('admin_menu', [$this, 'ShowPuginOnAdminMenu'] );
        add_action('admin_init', [$this, 'Settings']);
        add_action('the_content',[$this, 'ifWrap']);
        add_action('init',[$this, 'languages']);
    }

    function languages(){
        //echo dirname(plugin_basename(__FILE__)) . '/lang';
        load_plugin_textdomain('wcpdomain', false, dirname(plugin_basename(__FILE__)) . '/lang');
    }

    function ifWrap($content){
        if(is_single() and is_main_query()){
            foreach($GLOBALS['checkboxArray'] as $checkbox){            
                if(get_option($checkbox['db_name'])){
                    return $this->GenerateHTML($content);                    
                }
            }         
        }        
        
        return $content;
    }

    function GenerateHTML($content){
        $location = get_option('wcp_location');
        //$result = $content;     
        $wcp_content = '<h3>' . get_option('wcp_headline') .'</h3>';

        foreach($GLOBALS['checkboxArray'] as $checkbox){
            if(get_option($checkbox['db_name'])){
                $wcp_content = $wcp_content . $this::{$checkbox['func']}($content);                    
            }
        }
        $wcp_content = $wcp_content . '<hr class="section-break">';
        if($location){
            return $content . $wcp_content;
        }
        else{
            return $wcp_content . $content;
        }

        //return $result;
    }

    function CountingWord($content){
        return '<p>'.esc_html__('This article has', 'wcpdomain') . ' ' . str_word_count(strip_tags($content)) . ' '  . 'words'.'</p>';
    }

    function CountingCharacter($content){
        return '<p>'.esc_html__('This article has', 'wcpdomain') . ' '  . strlen(strip_tags($content)) . ' '   . 'Characters'.'</p>';
    }

    function estimateReadingTime($text, $wpm = 150) {
        $totalWords = str_word_count(strip_tags($text));
        $minutes = floor($totalWords / $wpm);
        $seconds = floor($totalWords % $wpm / ($wpm / 60));
        
        return '<p>'.'This article will take about' . ' '  . $minutes . ' '  . __('minute(s)', 'wcpdomain') . ' '  . $seconds . ' '  .__('second(s)', 'wcpdomain') . ' to read'.'</p>';
    }

    function ShowPuginOnAdminMenu(){
        /*
        1.name on browser tab
        2.name on admin menu
        3.the role can access this plugin
        4.slug
        5.render the plugin page
        */
        add_options_page(esc_html__('Word Count Settings', 'wcpdomain'),esc_html__('Word Count', 'wcpdomain'),
                                    'manage_options','word-count-setting-page',[$this, 'RenderSettingPage']);
    }
    
    function RenderSettingPage(){
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Word Count Settings', 'wcpdomain') ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('wordcountplugin');
            do_settings_sections('word-count-setting-page');
            submit_button();
            ?>
        </form>
    </div>
    <?php
    }

    function Settings(){
        add_settings_section('wcp_first_section', null, null, 'word-count-setting-page');
        
        add_settings_field('wcp_location', esc_html__('Display Location' ,'wcpdomain'), [$this, 'locationHTML'], 'word-count-setting-page', 'wcp_first_section');
        register_setting('wordcountplugin', 'wcp_location', [
            'sanitize_callback'=> [$this, 'sanitizeLocation'],
            'default'=>'0'
        ]);

        add_settings_field('wcp_headline', esc_html__('Headline Text' ,'wcpdomain'), [$this, 'headlineHTML'], 'word-count-setting-page', 'wcp_first_section');
        register_setting('wordcountplugin', 'wcp_headline', [
            'sanitize_callback'=> 'sanitize_text_field',
            'default'=>'Post Statisitics'
        ]);       

        foreach($GLOBALS['checkboxArray'] as $checkbox){
            add_settings_field($checkbox['db_name'], esc_html__($checkbox['label'] ,'wcpdomain'), [$this, 'CheckBoxHtml'], 'word-count-setting-page', 'wcp_first_section',$checkbox);
            register_setting('wordcountplugin', $checkbox['db_name'], [
                'sanitize_callback'=> 'sanitize_text_field',
                'default'=>'1'
            ]);
        }
    }

    function locationHTML(){
        ?>
        <select name="wcp_location" id="">
            <option value="0" <?php selected(get_option('wcp_location'), '0') ?>>Beginning of post</option>
            <option value="1" <?php selected(get_option('wcp_location'), '1') ?>>End of post</option>
        </select>
        <?php
    }

    function sanitizeLocation($input){
        if($input!='1' AND $input!='0'){
            add_settings_error('wcp_location', 'wcp_location_error', 'location value should be 0 or 1');
            return get_option('wcp_location');            
        }

        return $input;
    }

    function headlineHTML(){
        ?>
        <input type="text" name="wcp_headline" value="<?php echo esc_attr(get_option('wcp_headline')); ?>">
        <?php
    }

    function CheckBoxHtml($settingField){
        ?>
        <input type="checkbox" name="<?php echo $settingField['db_name'] ?>" id="" 
               value="<?php echo $settingField['value'] ?>" <?php checked(get_option( $settingField['db_name']),'1'); ?>>
        <?php
    }
}

$WordCountAndTimePlugin = new WordCountAndTimePlugin();

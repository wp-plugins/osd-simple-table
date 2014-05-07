<?php
/*
Plugin Name: OSD Simple Table Generator
Plugin URI: http://outsidesource.com
Description: A plugin that uses short tags to generate clean html tables on your wordpress site.
Version: 1.1
Author: OSD Web Development Team
Author URI: http://outsidesource.com
License: GPL2v2
*/
	
include('SimpleTable.php');

// Run SimpleTable
function osd_simple_table($content) {
	$options = get_option('osd_simple_table_options');
	$simpleTable = new SimpleTable($content, $options['line-break'], $options['row-delimiter'], $options['cell-delimiter']);
	return $simpleTable->get();
}
add_filter('the_content', 'osd_simple_table', 100);

/********************* ADMINISTRATION *************************/
if(is_admin()){
  add_action('admin_menu', 'osd_simple_table_options');
}

function osd_simple_table_options() {
	add_submenu_page( 'options-general.php', 'OSD Simple Table', 'OSD Simple Table', 'manage_options', 'osd-simple-table-options', 'osd_simple_table_options_callback' ); 
}

function osd_simple_table_options_callback() {
?>
    <style>
        #pre-div, #post-div {
            float: left;
            width: 50%;
        }
        .cont:after {
            content: "";
            display: block;
            clear: both;
        }
    </style>
	<h2>OSD Simple Table</h2>
    <p>
        This plugin makes inserting tables into your Wordpress content simple.<br>
        Here is an example of a simple table that you would put in your content wysiwyg:
    </p>
    <div class='cont'>
        <div id='pre-div'>
            [table th='1' tf='1' border='1px' width='100%' class='yay-a-table']<br />
            One|Two|Three|Four|Five;;<br />
            One|Two|Three|Four|Five;;<br />
            1|2|3|4|5;;<br />
            1[attr rowspan='2']|2|3|4|5;;<br />
            |2|3-5[attr colspan='3']||;;<br />
            1-2[attr colspan='2']||3|4-5[attr colspan='2']|;;<br />
            1-2[attr rowspan='3' colspan='2']||3-4[attr rowspan='2' colspan='2']||5;;<br />
            |||4|5;;<br />
            ||3|4|5;;<br />
            [/table]
            <p>This code will produce a table on the page that looks like:</p>
        </div>
        <div id='post-div'>
            <table class="simple-table yay-a-table" border="1px" width="100%"><thead><tr><th>One</th><th>Two</th><th>Three</th><th>Four</th><th>Five</th></tr></thead><tfoot><tr><td>One</td><td>Two</td><td>Three</td><td>Four</td><td>Five</td></tr></tfoot><tbody><tr><td>1</td><td>2</td><td>3</td><td>4</td><td>5</td></tr><tr><td rowspan="2">1</td><td>2</td><td>3</td><td>4</td><td>5</td></tr><tr><td>2</td><td colspan="3">3-5</td></tr><tr><td colspan="2">1-2</td><td>3</td><td colspan="2">4-5</td></tr><tr><td rowspan="3" colspan="2">1-2</td><td rowspan="2" colspan="2">3-4</td><td>5</td></tr><tr><td>5</td></tr><tr><td>3</td><td>4</td><td>5</td></tr></tbody></table>
        </div>
    </div>

    <h3>Format:</h3>
    <ul>
        <li>
            Define a table: <br />
            [table][/table]
        </li>
        <li>
            Specify row delimiter specific to the table<br>
            [table rd=';;'][/table]
        </li>
        <li>
            Specify column delimiter specific to the table<br>
            [table cd=';;'][/table]
        </li>
        <li>
            Give the table a header row and a border: <br />
            [table th='1' border='1px']
        </li>
        <li>
            Give the table a footer row and width: <br />
            [table tf='1' width='50%']
        </li>
        <li>
            Assign attributes to the table<br>
            [table id='my-id' data-test='test' class='my-class' style='color: red;']
        </li>
        <li>
            Assign attributes to a column<br>
            [table]<br>
            one[attr id='my-id' data-test='test' class='my-class' style='color:red;']|two|three;;<br>
            [/table]
        </li>
        <li>
            Span rows with a cell: <br />
            cell contents[attr rowspan='2']
        </li>
    </ul>
    <br>
    <h3>Current Global SimpleTable Settings:</h3>
	<form action="options.php" method="post">
    	<?php 
			settings_fields('osd_simple_table_options');
        	do_settings_sections(__FILE__);

			$options = get_option('osd_simple_table_options'); 
			$cell = (isset($options['cell-delimiter']) && $options['cell-delimiter'] != '') ? $options['cell-delimiter'] : '|';
			$row = (isset($options['row-delimiter']) && $options['row-delimiter'] != '') ? $options['row-delimiter'] : ';;';
			$line = (isset($options['line-break']) && $options['line-break'] != '') ? $options['line-break'] : '~~';
		?>
        <ul>
            <li>
                <label for='osd_simple_table_options[cell-delimiter]'>Cell Delimiter</label>
                <input type="text" name="osd_simple_table_options[cell-delimiter]" value="<?php echo $cell; ?>" />
            </li>
            <li>
                <label for='osd_simple_table_options[row-delimiter]'>Row Delimiter</label>
                <input type="text" name="osd_simple_table_options[row-delimiter]" value="<?php echo $row; ?>" />
            </li>
            <li>
                <label for='osd_simple_table_options[line-break]'>Line Break</label>
                <input type="text" name="osd_simple_table_options[line-break]" value="<?php echo $line; ?>" />
            </li>
        </ul>
        <input type="submit" class="submit button-primary" value="Save" />
    </form>
<?php
}

//register the options with wp
function osd_simple_table_register_options(){
    register_setting('osd_simple_table_options', 'osd_simple_table_options', 'osd_simple_table_options_validate');
}
add_action('admin_init', 'osd_simple_table_register_options');

//validate inputs
function osd_simple_table_options_validate($options) {
    if($options['cell-delimiter'] == "" || $options['row-delimiter'] == "" || $options['line-break'] == ""){
    	add_settings_error('osd_simple_table_settings', 'osd_simple_table_invalid_options', 'You must enter a value for each field.', $type = 'error');   
    }

    return $options;
}
?>
<?PHP
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */
if (!(in_array('index.php',get_included_files()))) 
{
    Header("Location: index.php" );
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Fire Soft Board 2</title>
        <meta charset="utf-8" />
        <link type="text/css" rel="stylesheet" href="style/main.css" />
        <link type="text/nonsense" rel="stylesheet" href="style/opera.css" />
    </head>
    <body>
    <div  class="main">
	<table width="100%">
	<tr>
		<td align="center" width="225" height="80" class="forum"><img src="style/logo.gif" alt="Installation" /></td>
		<td class="forum" align="center"></td>
	</tr>
	</table>
	<br />
    	<fieldset>
        <legend>List of files who must be writable :</legend>
            <dl class="title">
                <dt><label>Files :</label></dt>
                <dd><label>Is writable ?</label></dd>
            </dl>
            <?php foreach ($files as $file): ?>
             <dl class="desc">
                <dt><?php echo $file; ?></dt>
                <dd><input type="checkbox" disabled="disabled"  <?php echo (is_writable(ROOT.DIRECTORY_SEPARATOR.$file) ? 'checked="" class="good"' : 'class="bad"') ;?> /></dd>
             </dl>
            <?php endforeach; ?>
        </fieldset>
        <fieldset>
        	<legend>List of databases with their minimum version number</legend>
        	<dl class="title">
        		<dt><label>Databases</label></dt>
        		<dd><label>Is supported ?</label></dd>
        	</dl>
        	<?php foreach($databases as $database_name => $database_version): ?>
        		<dl class="desc">	
        			<dt><?php echo $database_version; ?></dt>
        			<dd><input type="checkbox" disabled="disabled"<?php echo (extension_loaded($database_name) ? 'checked="" class="good"' : 'class="bad"');?> /></dd>
        		</dl>
           <?php endforeach; ?>
        </fieldset>
        <fieldset>
        	<legend>Minimum version of PHP required</legend>
        	 <dl class="title">
        	 	<dt><label>Version</label></dt>
        	 	<dd><label>Is ok ?</label></dd>
        	 </dl>
        	 <dl class="desc">
        	 	<dt><?php echo $versions['php']; ?></dt>
        	    <dd><input type="checkbox" disabled="disabled"  <?php echo (version_compare(phpversion(),$versions['php'],">=") ? 'checked="" class="good"' : 'class="bad"');?> /></dd>
        	 </dl>
        </fieldset>
        <fieldset>
        	<legend>FTP options supported ?</legend>
        	<dl class="title">
        	 	<dt><label>Version</label></dt>
        	 	<dd><label>Is ok ?</label></dd>
        	 </dl>
        	<dl class="desc">
        		<dt>fsockopen function</dt>
        		<dd ><input type="checkbox" disabled="disabled"  <?php echo (function_exists('fsockopen')? 'checked="" class="good"' : 'class="bad"');?> /></dd>
        		<dt>FTP extension</dt>
        		<dd><input type="checkbox" disabled="disabled"  <?php echo (extension_loaded('ftp') ? 'checked="" class="good"' : 'class="bad"');?> /></dd>
        	</dl>	
        </fieldset>
        <fieldset>
        	<legend>List of optionnal extensions </legend>
        	<dl class="title">
        		<dt><label>Extension</label></dt>
        		<dd><label>Loaded ?</label><dd>
        	</dl>
        	<dl class="desc">
        		<?php foreach($extensions_optionnal as $extension): ?>
        			<dt><?php echo $extension; ?></dt>
        			<dd><input type="checkbox" disabled="disabled" <?php echo (extension_loaded($extension) ? 'checked="" class="good"' : 'class="bad"');?> /></dd>
        		<?php endforeach; ?>
        	</dl>
        </fieldset>
        <fieldset>
        	<legend>List of recommended extensions </legend>
        	<dl class="title">
        		<dt><label>Extension</label></dt>
        		<dd><label>Loaded ?</label><dd>
        	</dl>
        	<dl class="desc">
        		<?php foreach($extensions_recommended as $extension): ?>
        			<dt><?php echo $extension; ?></dt>
        			<dd><input type="checkbox" disabled="disabled"  <?php echo (extension_loaded($extension) ? 'checked="" class="good"' : 'class="bad"');?> /></dd>  			
        		<?php endforeach; ?>
        	</dl>
        </fieldset>
        <fieldset>
        	<legend>List of required extensions </legend>
        	<dl class="title">
        		<dt><label>Extension</label></dt>
        		<dd><label>Loaded ?</label><dd>
        	</dl>
        	<dl class="desc">
        		<?php foreach($extensions_required as $extension): ?>
        			<dt><?php echo $extension; ?></dt>
        			<dd><input type="checkbox" disabled="disabled"  <?php echo (extension_loaded($extension) ? 'checked="" class="good"' : 'class="bad"');?> /></dd>  			
        		<?php endforeach; ?>
        	</dl>
        </fieldset>
       </div>
    </body>
</html>
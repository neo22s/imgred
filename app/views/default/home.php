<?php require_once VIEWS_PATH.'header.php';?>

	<script type="text/javascript"> 
	function changeText(){
		var image="http://rir.li/"+document.getElementById('image').value;
		document.getElementById('t').innerHTML=image;
	}
	</script> 
        
    <h1><?php echo $content;?></h1> 
	<b>rir.li helps you prevent image hotlinking to other sites.</b> 
	Check our <a href="http://neo22s.com/wp-rir" >WordPress Plugin</a>.
	<br /> <br /> 
	
    <b>Usage:</b> http://rir.li/IMAGEURL (Accepted jpg, png, gif; Max size 500Kb)<br /> <br /> 
	<b>Example:</b> <a href="http://rir.li/http://linuxmedia.hu/images/tux.jpg" target="_blank">http://rir.li/http://linuxmedia.hu/images/tux.jpg</a> 
	<br /> 
	
	<input type="text" name="image" id="image" value="http://linuxmedia.hu/images/tux.jpg" size=80 onblur="if(this.value=='') this.value='http://linuxmedia.hu/images/tux.jpg';" onfocus="if(this.value=='http://linuxmedia.hu/images/tux.jpg') this.value='';" /> 
	<input type="submit" value="Go!" onclick="changeText();return false;"> 
	
	<br /> 
	<b>Here you have the image:</b><br /> 
	
	<textarea id="t" cols="50" rows="1" style="border:0;" onclick="this.select();">http://rir.li/http://linuxmedia.hu/images/tux.jpg</textarea> 
		
		
<?php require_once VIEWS_PATH.'footer.php';?>
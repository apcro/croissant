<div id="content_pri">
	{include file="shared/blocks/pulldown.tpl"}
	<h2>contact us</h2>
	<form action="/contact" method="POST" id="tellus">
		<fieldset>
			<div>
				<label for="contact_name">Name</label>
				<input type="text" size="30" id="contact_name" name="contact_name">
			</div>
			<div>
				<label for="contact_email">Email</label>
				<input type="text" size="30" id="contact_email" name="contact_email">
			</div>
			<div class="textarea">
				<label for="contact_message">Message</label>
				<textarea rows="7" cols="30" id="contact_message" name="contact_message"></textarea>
			</div>
			<div class="contact_captcha">
				<label for="contact_captcha">What is 3+2?</label>
				<input type="text" name="captcha" id="contact_captcha" />
			</div>
		</fieldset>
		<div class="submit">
			<a class="submit tellus">Submit</a>
			<input type="hidden" name="action" value="Submit" /> <span class="right">or e-mail us at <a href="mailto:contact@apsumon.com">contact@apsumon.com</a></span>
		</div>
	</form>
</div>
<div id="content_sec">
	{include file="shared/blocks/signup.tpl"}
	{include file="shared/blocks/specials.tpl"}
	{include file="shared/blocks/share.tpl"}
	{include file="shared/blocks/social.tpl"}
</div>

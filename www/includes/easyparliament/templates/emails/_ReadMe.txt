A note about the email templates.
=================================

The first line should start with 'Subject:' and include the 
default subject line for the mail. It's possible to override
this dynamically in the code.

The {TOKENS} will all be replaced with dynamic text when the 
email is sent. If you need to add new tokens when re-writing
an email, the code will need to be changed to make sure the
correct text is available.


For those that need to know, the emails are sent by
send_template_email() in utility.php.

-- 
phil@gyford.com
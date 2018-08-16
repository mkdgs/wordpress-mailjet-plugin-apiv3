<?php

namespace MailjetPlugin\Includes;

use Analog\Analog;

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Mailjet
 * @subpackage Mailjet/includes
 * @author     Your Name <email@example.com>
 */
class MailjetMail
{
    const MJ_HOST = 'in-v3.mailjet.com';
    const MJ_MAILER = 'X-Mailer:WP-Mailjet/0.1';

    public function __construct()
    {
        require_once ABSPATH . WPINC . '/class-phpmailer.php';
        require_once ABSPATH . WPINC . '/class-smtp.php';
        return new \PHPMailer(true);
    }

    public function sendMail($mailTransport)
    {
        try {
            //Server settings
            $mailTransport->SMTPDebug = 2;                                 // Enable verbose debug output
            $mailTransport->isSMTP();                                      // Set mailer to use SMTP
            $mailTransport->Host = 'smtp1.example.com;smtp2.example.com';  // Specify main and backup SMTP servers
            $mailTransport->SMTPAuth = true;                               // Enable SMTP authentication
            $mailTransport->Username = 'user@example.com';                 // SMTP username
            $mailTransport->Password = 'secret';                           // SMTP password
            $mailTransport->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = 587;                                    // TCP port to connect to

            //Recipients
            $mailTransport->setFrom('from@example.com', 'Mailer');
            $mailTransport->addAddress('joe@example.net', 'Joe User');     // Add a recipient
            $mailTransport->addAddress('ellen@example.com');               // Name is optional
            $mailTransport->addReplyTo('info@example.com', 'Information');
            $mailTransport->addCC('cc@example.com');
            $mailTransport->addBCC('bcc@example.com');

            //Attachments
            $mailTransport->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
            $mailTransport->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

            //Content
            $mailTransport->isHTML(true);                                  // Set email format to HTML
            $mailTransport->Subject = 'Here is the subject';
            $mailTransport->Body    = 'This is the HTML message body <b>in bold!</b>';
            $mailTransport->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mailTransport->send();
            echo 'Message has been sent';
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            echo 'Message could not be sent. Mailer Error: ', $mailTransport->ErrorInfo;
        }
    }

    public function phpmailer_init_smtp(\PHPMailer $phpmailer)
    {
        \MailjetPlugin\Includes\MailjetLogger::info('[ Mailjet ] [ ' . __METHOD__ . ' ] [ Line #' . __LINE__ . ' ] [ Configuring SMTP with Mailjet settings - Start ]');

        if (!get_option('mailjet_enabled') || 0 == get_option('mailjet_enabled')) {
            \MailjetPlugin\Includes\MailjetLogger::error('[ Mailjet ] [ ' . __METHOD__ . ' ] [ Line #' . __LINE__ . ' ] [ Mailjet not enabled ]');
            return;
        }

        $phpmailer->Mailer = 'smtp';
        $phpmailer->SMTPSecure = get_option('mailjet_ssl');

        $phpmailer->Host = self::MJ_HOST;
        $phpmailer->Port = get_option('mailjet_port');

        $phpmailer->SMTPAuth = TRUE;
        $phpmailer->Username = get_option('mailjet_apikey');
        $phpmailer->Password = get_option('mailjet_apisecret');

        $from_email = (get_option('mailjet_from_email') ? get_option('mailjet_from_email') : get_option('admin_email'));
        $phpmailer->From = $from_email;
        $phpmailer->Sender = $from_email;

        $phpmailer->FromName = get_option('mailjet_from_name') ? get_option('mailjet_from_name') : get_bloginfo('name');

        $phpmailer->AddCustomHeader(self::MJ_MAILER);

        \MailjetPlugin\Includes\MailjetLogger::info('[ Mailjet ] [ ' . __METHOD__ . ' ] [ Line #' . __LINE__ . ' ] [ Configuring SMTP with Mailjet settings - End ]');
    }


    public function wp_mail_failed_cb($wpError)
    {
        add_settings_error('mailjet_messages', 'mailjet_message', 'ERROR - '. $wpError->get_error_message(), 'error');
    }



    public static function sendTestEmail()
    {
        $test_sent = false;
        if (empty(get_option('mailjet_test_address'))) {
            \MailjetPlugin\Includes\MailjetLogger::error('[ Mailjet ] [ ' . __METHOD__ . ' ] [ Line #' . __LINE__ . ' ] [ Missing email address to send test email to ]');
            return;
        }
        // Send a test mail
        $subject = __('Your test mail from Mailjet', 'mailjet');
        $message = sprintf(__('Your Mailjet configuration is ok!' . 'SSL: %s Port: %s', 'mailjet'), (get_option('mailjet_ssl') ? 'On' : 'Off'), get_option('mailjet_port'));
        $test_sent = wp_mail(get_option('mailjet_test_address'), $subject, $message);

        if (!$test_sent) {
            \MailjetPlugin\Includes\MailjetLogger::error('[ Mailjet ] [ ' . __METHOD__ . ' ] [ Line #' . __LINE__ . ' ] [ Your test message was NOT sent, please review your settings ]');
            add_settings_error('mailjet_messages', 'mailjet_message', __('Your test message was NOT sent, please review your settings', 'mailjet'), 'error');
        } else {
            \MailjetPlugin\Includes\MailjetLogger::info('[ Mailjet ] [ ' . __METHOD__ . ' ] [ Line #' . __LINE__ . ' ] [ Your test message was sent succesfully ]');
            add_settings_error('mailjet_messages', 'mailjet_message', __('Your test message was sent succesfully', 'mailjet'), 'updated');
        }

    }

    public function wp_sender_email($original_email_address) {
        return get_option('mailjet_from_email');
    }

    public function wp_sender_name($original_email_from) {
        return get_option('mailjet_from_name');
    }

}
<?php
namespace Lab;

use PHPMailer\PHPMailer\PHPMailer;
use Exception;

final class EmailSender
{
    public function send(array $customer, array $job): string
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = Settings::required('MAILTRAP_HOST');
        $mail->Port       = (int)Settings::required('MAILTRAP_PORT');
        $mail->SMTPAuth   = true;
        $mail->Username   = Settings::required('MAILTRAP_USERNAME');
        $mail->Password   = Settings::required('MAILTRAP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->setFrom(getenv('MAIL_FROM_ADDRESS') ?: 'marketing@example.com', 'Marketing Team');

        $mail->addAddress($customer['email'], $customer['full_name']);

        $mail->Subject = $job['subject'];
        $mail->Body    = $job['body'];

        $mail->send();

        $delay = (int)(getenv('MAILTRAP_DELAY_SECONDS') ?: 11);
        if ($delay > 0) {
            sleep($delay);
        }

        return $mail->getLastMessageID();
    }
}
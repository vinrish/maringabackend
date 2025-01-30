<?php

namespace App\Mail;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Mpdf\Mpdf;

class PayslipMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $payroll;

    /**
     * Create a new message instance.
     */
    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Payslip for ' . $this->payroll->month . ' ' . $this->payroll->year
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.payslip', // Ensure this matches the path of your view file
            with: [
                'payroll' => $this->payroll,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // Create an instance of mPDF configured for A4 page
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4', // Set the page format to A4
            'orientation' => 'P', // Portrait orientation
            'default_font_size' => 10, // Adjust font size for readability
            'default_font' => 'Arial', // Set default font
            'margin_left' => 15, // Set margins for A4 layout
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
        ]);

        // Generate HTML for the PDF
        $html = view('pdf.payslip', ['payroll' => $this->payroll])->render();

        // Write HTML content to the PDF
        $mpdf->WriteHTML($html);

        // Set PDF protection (optional)
        $mpdf->SetProtection(['copy', 'print'], $this->payroll->employee->id_no);

        // Output the PDF as a string
        $pdfOutput = $mpdf->Output('', 'S');

        // Return the PDF as an attachment
        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(
                fn () => $pdfOutput,
                'payslip_' . $this->payroll->month . '_' . $this->payroll->year . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}

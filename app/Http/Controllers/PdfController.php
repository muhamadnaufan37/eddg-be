<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    /**
     * Generate PDF dengan ukuran kertas A4
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function generatePdf(Request $request)
    {
        try {
            // Data yang akan ditampilkan di PDF
            $data = [
                'title' => 'Contoh Dokumen PDF',
                'date' => date('d/m/Y'),
                'content' => 'Ini adalah contoh dokumen PDF yang dibuat menggunakan DomPDF dengan ukuran kertas A4.'
            ];

            // Load view untuk PDF
            $pdf = Pdf::loadView('pdf.document', $data);

            // Set ukuran kertas A4 dan orientasi portrait
            $pdf->setPaper('A4', 'portrait');

            // Set options tambahan (opsional)
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

            // Return PDF untuk di-download
            return $pdf->download('dokumen-' . time() . '.pdf');

            // Atau untuk stream (tampil di browser):
            // return $pdf->stream('dokumen.pdf');

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF dengan data custom
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function generateCustomPdf(Request $request)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'title' => 'nullable|string',
                'content' => 'nullable|string',
                'orientation' => 'nullable|in:portrait,landscape'
            ]);

            // Data yang akan ditampilkan di PDF
            $data = [
                'title' => $validated['title'] ?? 'Dokumen PDF',
                'date' => date('d/m/Y H:i:s'),
                'content' => $validated['content'] ?? 'Konten dokumen PDF',
            ];

            // Load view untuk PDF
            $pdf = Pdf::loadView('pdf.document', $data);

            // Set ukuran kertas A4 dan orientasi (default portrait)
            $orientation = $validated['orientation'] ?? 'portrait';
            $pdf->setPaper('A4', $orientation);

            // Return PDF untuk di-download
            return $pdf->download('dokumen-custom-' . time() . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stream PDF (tampilkan di browser tanpa download)
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function streamPdf(Request $request)
    {
        try {
            $data = [
                'title' => 'Preview Dokumen PDF',
                'date' => date('d/m/Y'),
                'content' => 'Dokumen ini ditampilkan di browser tanpa perlu didownload.'
            ];

            $pdf = Pdf::loadView('pdf.document', $data);
            $pdf->setPaper('A4', 'portrait');

            // Stream PDF (tampil di browser)
            return $pdf->stream('preview-dokumen.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}

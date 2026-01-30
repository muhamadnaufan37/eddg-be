<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>e-TBPKP - Pajak Air Permukaan</title>
    <style>
        /* Setup Ukuran A4 */
        @page {
            size: A4;
            margin: 0;
            /* Margin nol karena kita pakai padding di body untuk border biru */
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 20px 30px 20px 50px;
            /* Padding kiri lebih besar untuk space bar biru */
            line-height: 1.3;
            position: relative;
            background-color: #fff;
        }

        /* Border Biru di Sisi Kiri (Vertical Bar) */
        body::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 15px;
            /* Lebar bar biru */
            background-color: #2196F3;
        }

        /* Helper Classes */
        .text-bold {
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .blue-text {
            color: #2196F3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        /* --- Header Section --- */
        .header-table {
            border-bottom: 2px solid #2196F3;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header-logo img {
            width: 70px;
            height: auto;
        }

        .header-title {
            text-align: center;
        }

        .header-title h1 {
            margin: 0;
            font-size: 26px;
            color: #2196F3;
            font-weight: bold;
        }

        .header-title p {
            margin: 2px 0;
            font-size: 10px;
            font-weight: bold;
            color: #444;
        }

        .header-info-box {
            font-size: 10px;
            border-left: 1px solid #ddd;
            padding-left: 15px;
        }

        /* --- Info Gray Box --- */
        .info-gray-container {
            background-color: #F2F7FA;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
        }

        .qr-wrapper {
            background: white;
            border: 1px solid #cce0ff;
            border-radius: 5px;
            padding: 8px;
            width: 240px;
            display: inline-block;
        }

        /* --- Card Blue Styling --- */
        .card {
            border: 1px solid #2196F3;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .card-header {
            background-color: #2196F3;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }

        .card-body {
            padding: 12px 15px;
        }

        .data-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .label-width {
            width: 160px;
            font-weight: bold;
        }

        .sep-width {
            width: 15px;
        }

        /* --- Calculation Table --- */
        .calc-table {
            border: 1px solid #2196F3;
            border-radius: 10px;
            overflow: hidden;
            /* Penting untuk border rounded */
        }

        .calc-table th {
            background-color: #2196F3;
            color: white;
            padding: 10px;
            font-size: 11px;
        }

        .calc-table td {
            padding: 10px;
            border-bottom: 1px solid #e0f0ff;
        }

        .row-terbilang {
            background-color: #F5F5F5;
            font-style: italic;
        }

        /* --- Footer --- */
        .footer {
            margin-top: 30px;
            text-align: center;
        }

        .footer img {
            height: 35px;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td width="15%" class="header-logo">
                <img src="https://upload.wikimedia.org/wikipedia/commons/b/b2/Lambang_Pemerintah_Provinsi_Jawa_Barat.svg" alt="Prov Jabar">
            </td>
            <td width="55%" class="header-title">
                <h1>e-TBPKP</h1>
                <p>TANDA BUKTI PELUNASAN KEWAJIBAN PEMBAYARAN ELEKTRONIK</p>
                <p>PAJAK AIR PERMUKAAN PROVINSI JAWA BARAT</p>
            </td>
            <td width="30%">
                <table class="header-info-box">
                    <tr>
                        <td><strong>No</strong></td>
                        <td>: 20220202930</td>
                    </tr>
                    <tr>
                        <td><strong>No. Kohir</strong></td>
                        <td>: 01.0001.01.23</td>
                    </tr>
                    <tr>
                        <td><strong>Tgl Cetak</strong></td>
                        <td>: 30/10/2023 10:45</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="info-gray-container">
        <table width="100%">
            <tr>
                <td width="55%">
                    <table class="data-table">
                        <tr>
                            <td class="label-width">Masa Pajak</td>
                            <td class="sep-width">:</td>
                            <td>Oktober 2023</td>
                        </tr>
                        <tr>
                            <td class="label-width">Tanggal Penetapan</td>
                            <td class="sep-width">:</td>
                            <td>25 Oktober 2023</td>
                        </tr>
                        <tr>
                            <td class="label-width">Tanggal Jatuh Tempo</td>
                            <td class="sep-width">:</td>
                            <td>30 Oktober 2023</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <div class="card-header">Data Wajib Pajak</div>
        <div class="card-body">
            <table class="data-table">
                <tr>
                    <td class="label-width">NPWP</td>
                    <td class="sep-width">:</td>
                    <td>53.088.406.0-686.000.02</td>
                </tr>
                <tr>
                    <td class="label-width">Nama Perusahaan</td>
                    <td class="sep-width">:</td>
                    <td class="text-bold">PT. AIR MENGALIR JERNIH</td>
                </tr>
                <tr>
                    <td class="label-width">Alamat Perusahaan</td>
                    <td class="sep-width">:</td>
                    <td>Jl. Cikutra, Gg. Sukarapih 2 No. 54, Bandung</td>
                </tr>
                <tr>
                    <td class="label-width">Nama WP / Kuasa</td>
                    <td class="sep-width">:</td>
                    <td>BUDI SANTOSO</td>
                </tr>
                <tr>
                    <td class="label-width">Alamat WP</td>
                    <td class="sep-width">:</td>
                    <td>Jl. Cikutra, Gg. Sukarapih 2. No. 54 RT/RW 001/015</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Dasar Pengenaan Pajak</div>
        <div class="card-body">
            <table class="data-table">
                <tr>
                    <td class="label-width">Volume Pengambilan</td>
                    <td class="sep-width">:</td>
                    <td class="text-right">10.000 M³</td>
                </tr>
                <tr>
                    <td class="label-width">Pemanfaatan Air</td>
                    <td class="sep-width">:</td>
                    <td class="text-right">10.000 M3</td>
                </tr>
                <tr>
                    <td class="label-width">Produksi Listrik</td>
                    <td class="sep-width">:</td>
                    <td class="text-right">5000 Kwh</td>
                </tr>
                <tr>
                    <td class="label-width">Luas Pertanian</td>
                    <td class="sep-width">:</td>
                    <td class="text-right">100 M2</td>
                </tr>
                <tr>
                    <td class="label-width">NPA</td>
                    <td class="sep-width">:</td>
                    <td class="text-right">Rp. 3.116.432,00</td>
                </tr>
                <tr>
                    <td class="label-width">HDA</td>
                    <td class="sep-width">:</td>
                    <td class="text-right">Rp. 687,00</td>
                </tr>
            </table>
        </div>
    </div>

    <table class="calc-table">
        <thead>
            <tr>
                <th class="text-left">URAIAN JENIS PAJAK</th>
                <th width="25%" class="text-right">POKOK</th>
                <th width="25%" class="text-right">DENDA ADM.</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-bold">Pajak Air Permukaan (PAP)</td>
                <td class="text-right">Rp. 311.700,00</td>
                <td class="text-right">Rp. 0,00</td>
            </tr>
            <tr>
                <td class="text-left text-bold">Total Tagihan</td>
                <td colspan="2" class="text-right text-bold" style="font-size: 13px;">Rp. 311.700,00</td>
            </tr>
            <tr class="row-terbilang">
                <td class="text-bold">Dengan Huruf</td>
                <td colspan="2" class="text-right text-bold uppercase">Tiga Ratus Sebelas Ribu Tujuh Ratus Rupiah</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p style="font-size: 9px; color: #777; margin-bottom: 10px;">Terima kasih telah berkontribusi bagi pembangunan Jawa Barat melalui pembayaran pajak.</p>
        <img src="https://bapenda.jabarprov.go.id/wp-content/uploads/2021/03/Logo-Bapenda-2021.png" alt="Logo Bapenda">
    </div>

</body>

</html>
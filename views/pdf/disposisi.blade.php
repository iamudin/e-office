<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>PDF</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 0;
			padding: 0;
			font-size: 14px;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
		}
		
		.header {
			width: 100%;
			border-bottom: 2px solid #000;
			padding-bottom: 10px;
			margin-bottom: 20px;
		}

		.header img {
			width: 80px;
			height: auto;
		}
		.header-title {
			text-align: center;
		}
		.header-title h1 {
			margin: 0;
			font-size: 18px;
			text-transform: uppercase;
		}
		.header-title h2 {
			margin: 0;
			font-size: 14px;
			font-weight: normal;
		}
		.header-title p {
			margin: 0;
			font-size: 12px;
		}

		th, td {
			border: 1px solid #000;
			padding: 8px;
			text-align: left;
			vertical-align: top;
		}

		th {
			text-align: center;
			font-weight: bold;
		}

		.checklist-box {
			width: 15px;
			height: 15px;
			border: 1px solid #000;
			display: inline-block;
			margin-right: 5px;
			vertical-align: middle;
		}

		.disposisi-table td {
			width: 50%;
		}

		.no-border {
			border: none !important;
		}
	</style>
</head>
<body>
	<table class="header no-border">
		<tr>
			<td width="15%" class="no-border text-center" style="text-align: center;">
				<img src="{{ config('earsip.logo') }}" alt="Logo" style="width: 70px;">
			</td>
			<td width="85%" class="no-border header-title">
				<h1 style="font-size: 22px;">PEMERINTAH KABUPATEN BENGKALIS</h1>
				<h2 style="font-size: 18px; font-weight: bold;">DINAS PERKEBUNAN</h2>
				<p>Jalan Pertanian, Bengkalis, Riau 28711</p>
			</td>
		</tr>
	</table>

	<h2 style="text-align: center; margin: 10px 0; text-decoration: underline; font-size: 16px;">LEMBAR DISPOSISI</h2>

	<table>
		<tr>
			<td width="50%">
				<strong>Surat Dari:</strong> {{ $data->surat_dari }}<br><br>
				<strong>Tanggal Surat:</strong> {{ $data->tanggal_surat->format('d/m/Y') }}<br><br>
				<strong>Nomor Surat:</strong> {{ $data->nomor_surat }}
			</td>
			<td width="50%">
				<strong>Diterima Tanggal:</strong> {{ $data->tanggal_terima->format('d/m/Y') }}<br><br>
				<strong>Nomor Agenda:</strong> {{ $data->nomor_agenda }}<br><br>
				<strong>Sifat:</strong> {{ $data->sifat }}
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<strong>Hal:</strong><br>
				{{ $data->hal }}
			</td>
		</tr>
	</table>

	<table class="disposisi-table">
		<tr>
			<th>DITERUSKAN KEPADA SDR/I :</th>
			<th>DENGAN HORMAT HARAP :</th>
		</tr>
		<tr>
			<td>
				@foreach($penerima as $row)
				<div style="margin-bottom: 5px;">
					<div class="checklist-box" style="{{ $row->id == $data->disposisis[0]->pejabat_id ? 'background-color: black;' : '' }}"></div>
					{{ $row->jabatan }}
				</div>
				@endforeach
			</td>
			<td>
				@foreach(['Tanggapan dan Saran', 'Proses Lebih lanjut', 'Koordinasi / Konfirmasikan'] as $row)
				<div style="margin-bottom: 5px;">
					<div class="checklist-box" style="{{ is_array($data->harapan) && in_array($row, $data->harapan) ? 'background-color: black;' : '' }}"></div>
					{{ $row }}
				</div>
				@endforeach
			</td>
		</tr>
		<tr>
			<td colspan="2" style="height: 100px;">
				<strong>Catatan:</strong><br>
				{{ $data->catatan }}
			</td>
		</tr>
	</table>
    <br><br>
    <div style="width: 100%; text-align: right;">
        @if($data->kadis)
        <div style="float: right; width: 300px; text-align: center;">
            <strong>KEPALA DINAS PERKEBUNAN</strong><br>
            <br><br><br>
            <strong><u>{{ $data->kadis->nama }}</u></strong><br>
            {{ $data->kadis->pangkat_golongan }}<br>
            NIP. {{ $data->kadis->nip }}
        </div>
        @endif
        <div style="clear: both;"></div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Rekap Surat Masuk</title>
	<style>
		body { font-family: Arial, sans-serif; font-size: 12px; }
		.header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
		.header h1 { margin: 0; font-size: 18px; }
		.header h2 { margin: 0; font-size: 14px; font-weight: normal; }
		table { width: 100%; border-collapse: collapse; margin-top: 10px; }
		th, td { border: 1px solid #000; padding: 5px; vertical-align: top; }
		th { background-color: #f0f0f0; text-align: center; }
	</style>
</head>
<body>
	<div class="header">
		<h1>REKAPITULASI SURAT MASUK</h1>
		<h2>{{ config('e-office.title', 'E-Arsip') }} - Periode: {{ $periode }}</h2>
	</div>

	<table>
		<thead>
			<tr>
				<th width="5%">No</th>
				<th width="15%">Pengirim</th>
				<th width="10%">Tanggal Surat</th>
				<th width="15%">Nomor Surat</th>
				<th width="25%">Perihal</th>
				<th width="10%">Diterima</th>
				<th width="20%">Status Disposisi</th>
			</tr>
		</thead>
		<tbody>
			@forelse($rekap as $index => $row)
			<tr>
				<td style="text-align: center;">{{ $index + 1 }}</td>
				<td>{{ $row->surat_dari }}</td>
				<td>{{ $row->tanggal_surat->format('d/m/Y') }}</td>
				<td>{{ $row->nomor_surat }}</td>
				<td>{{ $row->hal }}</td>
				<td>{{ $row->tanggal_terima->format('d/m/Y') }}</td>
				<td>
					@if($row->disposisis->count() > 0)
						Diteruskan ke: <br>
						@foreach($row->disposisis as $d)
							- {{ $d->pejabat->jabatan }}<br>
						@endforeach
					@elseif($row->diteruskan_ke_kadis)
						Menunggu Kadis
					@elseif($row->paraf_kasubagumum_pada)
						Menunggu Diteruskan
					@else
						Belum Diproses
					@endif
				</td>
			</tr>
			@empty
			<tr>
				<td colspan="7" style="text-align: center;">Tidak ada data surat masuk pada periode ini.</td>
			</tr>
			@endforelse
		</tbody>
	</table>
</body>
</html>

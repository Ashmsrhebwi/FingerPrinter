<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $user->name ?? 'Kullanıcı' }} | Giriş / Çıkış Detayları</title>

  <link rel="stylesheet" href="{{ asset('assets/attendance.css') }}">
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />

  @php use Carbon\Carbon; @endphp

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: #f7f9fb;
    }
    .details-container {
      max-width: 900px;
      margin: 50px auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,.1);
    }
    h2 { color:#194a64; text-align:center; }
    .subtitle { text-align:center; color:#555; margin-bottom:25px; }

    .attendance-table { width:100%; border-collapse:collapse; }
    .attendance-table th {
      background:#194a64; color:#fff; padding:12px;
    }
    .attendance-table td {
      padding:10px; border-bottom:1px solid #ddd;
    }

    .status-normal { color:#2ecc71; font-weight:bold; }
    .status-late { color:#e67e22; font-weight:bold; }
    .status-absent { color:#c0392b; font-weight:bold; }

    .user-badge {
      font-size:12px;
      padding:4px 8px;
      border-radius:6px;
      margin-left:8px;
      font-weight:bold;
    }
    .badge-active { background:#2ecc71; color:#fff; }
    .badge-passive { background:#e74c3c; color:#fff; }

    .back-btn {
      display:inline-block;
      background:#194a64;
      color:#fff;
      padding:10px 18px;
      border-radius:8px;
      text-decoration:none;
      margin-top:20px;
    }
  </style>
</head>
<body>

<div class="details-container">
  <h2>
    <i class="bi bi-clock-history"></i>
    {{ $user->name }}

    @if($user->status === 'Pasif')
      <span class="user-badge badge-passive">PASİF KULLANICI</span>
    @else
      <span class="user-badge badge-active">AKTİF</span>
    @endif
  </h2>

  <p class="subtitle">📅 Cumartesi ve Pazar hariç tüm giriş / çıkış kayıtları</p>

  <table class="attendance-table">
    <thead>
      <tr>
        <th>Tarih</th>
        <th>Giriş</th>
        <th>Çıkış</th>
        <th>Durum</th>
      </tr>
    </thead>
    <tbody>
      @foreach($records as $item)
        @php
          $day = Carbon::parse($item->date)->locale('tr')->dayName;
          $cls = match($item->status) {
            'Normal' => 'status-normal',
            'Geç Geldi' => 'status-late',
            'Gelmedi' => 'status-absent',
            default => ''
          };
        @endphp

        @if(!in_array($day,['Cumartesi','Pazar']))
        <tr>
          <td>{{ ucfirst($day) }} - {{ $item->date }}</td>
          <td>{{ $item->in_time ?? '-' }}</td>
          <td>{{ $item->out_time ?? '-' }}</td>
          <td class="{{ $cls }}">{{ $item->status }}</td>
        </tr>
        @endif
      @endforeach
    </tbody>
  </table>

  <div style="text-align:center">
    <a href="{{ route('dashboard') }}" class="back-btn">
      <i class="bi bi-arrow-left-circle"></i> Geri Dön
    </a>
  </div>
</div>

</body>
</html>

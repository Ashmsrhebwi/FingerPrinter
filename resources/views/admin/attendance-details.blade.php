<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $user->name }} | Giriş / Çıkış Detayları</title>
  <link rel="stylesheet" href="{{ asset('assets/attendance.css') }}">
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
  @php use Carbon\Carbon; @endphp
  <style>
    .status-normal { color: #2ecc71; font-weight: bold; }
    .status-late { color: #e67e22; font-weight: bold; }
    .status-absent { color: #c0392b; font-weight: bold; }
  </style>
</head>
<body>
  <div class="details-container">
<h2><i class="bi bi-clock-history"></i> Tüm Kullanıcıların Kayıtları</h2>
    <p class="subtitle">📅 Cumartesi ve Pazar hariç tüm giriş / çıkış kayıtları listelenmiştir.</p>

    <table class="attendance-table">
      <thead>
        <tr>
          <th>Tarih (Gün)</th>
          <th>Giriş Saati</th>
          <th>Çıkış Saati</th>
          <th>Durum</th>
        </tr>
      </thead>
      <tbody>
        @foreach($records as $item)
          @php
            $dayName = Carbon::parse($item->date)->locale('tr')->dayName;
            $statusClass = match($item->status) {
                'Normal' => 'status-normal',
                'Geç Geldi' => 'status-late',
                'Gelmedi' => 'status-absent',
                default => ''
            };
          @endphp
          @if(!in_array($dayName, ['Cumartesi','Pazar']))
            <tr>
              <td>{{ ucfirst($dayName) }} - {{ $item->date }}</td>
              <td>{{ $item->in_time ?? '-' }}</td>
              <td>{{ $item->out_time ?? '-' }}</td>
              <td class="{{ $statusClass }}">{{ $item->status ?? '-' }}</td>
            </tr>
          @endif
        @endforeach
      </tbody>
    </table>

    <div style="text-align:center; margin-top:20px;">
      <a href="{{ route('dashboard') }}" class="back-btn">
        <i class="bi bi-arrow-left-circle"></i> Geri Dön
      </a>
    </div>
  </div>
</body>
</html>

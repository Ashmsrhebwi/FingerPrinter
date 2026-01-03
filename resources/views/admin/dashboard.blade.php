<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Yönetici Paneli</title>

  <!-- ✅ CSS -->
  <!-- <link rel="stylesheet" href="{{ asset('assets/style.css') }}" /> -->
  <link rel="stylesheet" href="{{ asset('assets/dashboard.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/attendance.css') }}" />
  <link rel="stylesheet" href="{{ asset('assets/users.css') }}">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
</head>
<body>

  <!-- ✅ الشريط العلوي -->
  <div class="navbar">
    <div class="logo"><i class="bi bi-gear-fill"></i> Yönetici Paneli</div>
    <a href="/logout" class="logout-btn" id="logoutBtn">
      <i class="bi bi-box-arrow-right"></i> Çıkış Yap
    </a>
  </div>

  @if(session('success'))
  <div style="color: green; margin: 10px; text-align:center;">
    {{ session('success') }}
  </div>
  @endif

  <!-- ✅ المحتوى الرئيسي -->
  <div class="main-content">
    <p class="welcome">
      <i class="bi bi-sun-fill sun"></i>
      Hoş geldiniz, <strong>Admin</strong>!
    </p>

    <div class="cards-container">
      <div class="card">
        <h3><i class="bi bi-people-fill"></i> Kullanıcı Bilgileri</h3>
        <p>Sistemde kayıtlı kullanıcıları görüntüleyebilir, düzenleyebilir veya silebilirsiniz.</p>
        <button onclick="showSection('users')">Kullanıcıları Görüntüle</button>
      </div>

      <div class="card">
        <h3><i class="bi bi-person-badge"></i> Admin Bilgileri</h3>
        <p>Yeni admin ekleyebilir veya mevcut adminleri düzenleyebilirsiniz.</p>
        <button onclick="showSection('admins')">Adminleri Görüntüle</button>
      </div>

      <div class="card">
        <h3><i class="bi bi-clock-history"></i> Giriş / Çıkış Kayıtları</h3>
        <p>Personellerin günlük giriş-çıkış saatlerini görüntüleyin.</p>
        <button onclick="showSection('attendance')">Kayıtları Görüntüle</button>
      </div>
    </div>

    <!-- ✅ قسم المستخدمين -->
    <!-- ✅ قسم المستخدمين -->
<section id="usersSection" class="users-section hidden">
  <h2><i class="bi bi-people"></i> Kullanıcı Listesi</h2>

  <div class="users-table-container">
    <table class="users-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Ad Soyad</th>
          <th>Departman</th>
          <th>Birim</th>
          <th>Durum</th>
          <th>İşlemler</th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $user)
          <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->dept ?? '-' }}</td>
            <td>{{ $user->unit ?? '-' }}</td>
            <td>{{ $user->status }}</td>
            <td>
              <!-- ✏️ تعديل -->
              <button class="edit-btn"
                onclick="editUser({{ $user->id }}, '{{ $user->name }}', '{{ $user->dept }}', '{{ $user->unit }}', '{{ $user->status }}')">
                <i class="bi bi-pencil-square"></i> Düzenle
              </button>

              <!-- 🗑️ حذف -->
              <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline-block;">
                @csrf
                @method('DELETE')
                <button type="submit" class="delete-btn"
                  onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                  <i class="bi bi-trash"></i> Sil
                </button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- ➕ زر إضافة -->
  <button id="addUserBtn" class="add-user-btn" onclick="toggleAddUserForm()">
    <i class="bi bi-plus-square"></i> Yeni Kullanıcı Ekle
  </button>

  <!-- ✅ نموذج إضافة / تعديل مستخدم -->
<!-- ✅ نموذج إضافة / تعديل مستخدم -->
<!-- ✅ نموذج إضافة / تعديل مستخدم -->
<div id="addUserForm" class="add-user-form hidden">
  <h3 id="userFormTitle">Yeni Kullanıcı Ekle</h3>

  <form id="userForm" method="POST" action="{{ route('users.store') }}">
    @csrf
    <input type="hidden" name="_method" id="userFormMethod" value="POST">

    <!-- 🔑 user_id للبصمة -->
    <input type="hidden" id="fingerUserId">

    <div class="form-group">
      <label>Ad Soyad</label>
      <input type="text" id="userName" name="name" required />
    </div>

    <div class="form-group">
      <label>Departman</label>
      <input type="text" id="userDept" name="dept" />
    </div>

    <div class="form-group">
      <label>Birim</label>
      <input type="text" id="userUnit" name="unit" />
    </div>

    <div class="form-group">
      <label>Durum</label>
      <select id="userStatus" name="status" required>
        <option value="Aktif">Aktif</option>
        <option value="Pasif">Pasif</option>
      </select>
    </div>

    <!-- ✅ زر البصمة -->
    <div class="form-group">
      <label>Biyometrik Kayıt</label>
      <small style="color:#555; display:block; margin-bottom:8px;">
        🔒 Parmak kaydı yalnızca yönetici tarafından başlatılır.
      </small>

      <button 
        type="button"
        class="finger-btn"
        onclick="startFingerprintRegister()">
        <i class="bi bi-fingerprint"></i>
        Parmak Kaydını Başlat
      </button>
    </div>

    <div class="form-actions">
      <button type="submit" class="save-btn">
        <i class="bi bi-save2-fill"></i> Kaydet
      </button>
      <button type="button" class="cancel-btn" onclick="cancelUserEdit()">
        <i class="bi bi-x-circle"></i> İptal
      </button>
    </div>
  </form>
</div>

<!-- ✅ قسم البصمات خارج الفورم -->
<section id="fingerSection" class="users-section hidden">
  <h2><i class="bi bi-fingerprint"></i> Kullanıcı Biyometrik Verileri</h2>

  <table class="users-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Kullanıcı Adı</th>
        <th>Biyometrik Veri</th>
        <th>Oluşturulma</th>
      </tr>
    </thead>
    <tbody>
      @foreach($fingerprints as $fp)
        <tr>
          <td>{{ $fp->id }}</td>
          <td>{{ $fp->user->name ?? '-' }}</td>
          <td>{{ Str::limit($fp->finger_data, 20, '...') }}</td>
          <td>{{ $fp->created_at->format('Y-m-d H:i') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</section>

</section>


    <!-- ✅ قسم الأدمن -->
    <section id="adminsSection" class="users-section hidden">
      <h2><i class="bi bi-person-badge"></i> Admin Listesi</h2>

      <div class="users-table-container">
        <table class="users-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Email</th>
              <th>Oluşturulma Tarihi</th>
              <th>İşlemler</th>
            </tr>
          </thead>
          <tbody>
            @foreach($admins as $admin)
              <tr>
                <td>{{ $admin->id }}</td>
                <td>{{ $admin->email }}</td>
                <td>{{ $admin->created_at }}</td>
                <td>
                  <button class="edit-btn" onclick="editAdmin({{ $admin->id }}, '{{ $admin->email }}')"><i class="bi bi-pencil-square"></i> Düzenle</button>
                  <form action="{{ route('admins.destroy', $admin->id) }}" method="POST" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="delete-btn" onclick="return confirm('Bu admini silmek istediğinize emin misiniz?')"><i class="bi bi-trash"></i> Sil</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <button class="add-user-btn" onclick="toggleAddAdminForm()">
        <i class="bi bi-person-plus"></i> Yeni Admin Ekle
      </button>

      <div id="addAdminForm" class="add-user-form hidden">
        <h3 id="adminFormTitle">Yeni Admin Ekle</h3>
        <form id="adminForm" method="POST" action="{{ route('admins.store') }}">
          @csrf
          <input type="hidden" name="_method" id="adminFormMethod" value="POST">

          <div class="form-group">
            <label>Email</label>
            <input type="email" id="adminEmail" name="email" required />
          </div>

          <div class="form-group">
            <label>Şifre</label>
            <input type="password" id="adminPassword" name="password" required />
          </div>

          <div class="form-actions">
            <button type="submit" class="save-btn"><i class="bi bi-save2-fill"></i> Kaydet</button>
            <button type="button" class="cancel-btn" onclick="cancelAdminEdit()"><i class="bi bi-x-circle"></i> İptal</button>
          </div>
        </form>
      </div>
    </section>

    <!-- ✅ قسم الحضور -->
<section id="attendanceSection" class="attendance-section hidden">
  <h2><i class="bi bi-clock"></i> Giriş / Çıkış Kayıtları</h2>

  <div class="attendance-cards-container">
    @foreach($users as $user)
      <div class="attendance-card">
        <div class="card-header">
          <h3><i class="bi bi-person-circle"></i> {{ $user->name }}</h3>
          <p>{{ $user->dept ?? 'Departman Yok' }} - {{ $user->unit ?? 'Birim Yok' }}</p>
        </div>

        @php
          $latest = $attendance->where('user_id', $user->id)->sortByDesc('date')->first();
        @endphp

        <div class="card-body">
          @if($latest)
            {{-- ما نعرض التفاصيل هون --}}
            <p class="small-text text-muted">Son kayıt mevcut.</p>
          @else
            <p class="no-data">Henüz kayıt bulunamadı.</p>
          @endif
          @if($user->status === 'Pasif')
            <span class="user-badge badge-passive">PASİF KULLANICI</span>
          @endif
        </div>

        <div class="card-footer">
<a href="{{ route('attendance.show', $user->id) }}" class="details-btn">
  <i class="bi bi-eye"></i> Detayları Gör
</a>

        </div>
      </div>
    @endforeach
  </div>
</section>


  </div>

  <!-- ✅ Scripts -->
 
  <script>
/* ================================
   🟢 Fingerprint Register
================================ */
function startFingerprintRegister() {
  const userId = document.getElementById('fingerUserId')?.value;

  if (!userId) {
    alert("⚠️ Önce kullanıcıyı kaydedin veya düzenleyin.");
    return;
  }

  if (!confirm("Parmak kaydı başlatılsın mı?")) return;

  fetch('/fingerprint/start-register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document
        .querySelector('meta[name="csrf-token"]')
        .content
    },
    body: JSON.stringify({
      user_id: userId
    })
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message ?? "Parmak kaydı başlatıldı. Cihaza gidin.");
  })
  .catch(err => {
    console.error(err);
    alert("❌ Sunucu hatası");
  });
}

/* ================================
   🟢 Sections
================================ */
function showSection(section) {
  ['users', 'admins', 'attendance'].forEach(id =>
    document.getElementById(id + 'Section')?.classList.add('hidden')
  );
  document.getElementById(section + 'Section')?.classList.remove('hidden');
}

/* ================================
   🟢 Users
================================ */
function toggleAddUserForm() {
  document.getElementById('addUserForm').classList.toggle('hidden');
  resetUserForm();
}

function editUser(id, name, dept, unit, status) {
  const form = document.getElementById('userForm');

  form.action = `/users/${id}`;
  document.getElementById('userFormMethod').value = 'PUT';
  document.getElementById('userFormTitle').textContent = 'Kullanıcı Düzenle';

  document.getElementById('userName').value = name;
  document.getElementById('userDept').value = dept;
  document.getElementById('userUnit').value = unit;
  document.getElementById('userStatus').value = status;

  // ⭐ أهم سطر للبصمة
  document.getElementById('fingerUserId').value = id;

  document.getElementById('addUserForm').classList.remove('hidden');
}

function cancelUserEdit() {
  document.getElementById('addUserForm').classList.add('hidden');
  resetUserForm();
}

function resetUserForm() {
  const form = document.getElementById('userForm');

  form.action = "{{ route('users.store') }}";
  document.getElementById('userFormMethod').value = 'POST';
  document.getElementById('userFormTitle').textContent = 'Yeni Kullanıcı Ekle';

  document.getElementById('userName').value = '';
  document.getElementById('userDept').value = '';
  document.getElementById('userUnit').value = '';
  document.getElementById('userStatus').value = 'Aktif';
  document.getElementById('fingerUserId').value = '';
}

/* ================================
   🟢 Admins
================================ */
function toggleAddAdminForm() {
  document.getElementById('addAdminForm').classList.toggle('hidden');
  resetAdminForm();
}

function editAdmin(id, email) {
  const form = document.getElementById('adminForm');

  form.action = `/admins/${id}`;
  document.getElementById('adminFormMethod').value = 'PUT';
  document.getElementById('adminFormTitle').textContent = 'Admin Düzenle';

  document.getElementById('adminEmail').value = email;
  document.getElementById('addAdminForm').classList.remove('hidden');
}

function cancelAdminEdit() {
  document.getElementById('addAdminForm').classList.add('hidden');
  resetAdminForm();
}

function resetAdminForm() {
  const form = document.getElementById('adminForm');

  form.action = "{{ route('admins.store') }}";
  document.getElementById('adminFormMethod').value = 'POST';
  document.getElementById('adminFormTitle').textContent = 'Yeni Admin Ekle';

  document.getElementById('adminEmail').value = '';
  document.getElementById('adminPassword').value = '';
}
</script>


</body>
</html>

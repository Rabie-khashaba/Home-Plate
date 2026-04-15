<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء رابط دفع</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f9; margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .container { width:100%; max-width:560px; background:#fff; padding:22px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.12); }
        h1 { margin:0 0 14px; font-size:22px; }
        label { display:block; margin:10px 0 6px; color:#333; }
        input { width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:8px; outline:none; }
        .row { display:flex; gap:10px; }
        .row > div { flex:1; }
        .btn { margin-top:14px; width:100%; padding:12px; border:0; border-radius:8px; background:#2563eb; color:#fff; font-weight:700; cursor:pointer; }
        .error { background:#fee2e2; color:#991b1b; padding:10px 12px; border-radius:8px; margin-bottom:12px; }
    </style>
</head>
<body>
<div class="container">
    <h1>إنشاء رابط دفع (Paymob)</h1>

    @if ($errors->any())
        <div class="error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('payment.process.web') }}">
        @csrf

        <div class="row">
            <div>
                <label>المبلغ</label>
                <input name="amount" type="number" step="0.01" value="{{ old('amount', 1) }}" required>
            </div>
            <div>
                <label>العملة</label>
                <input name="currency" type="text" value="{{ old('currency', 'EGP') }}" required>
            </div>
        </div>

        <div class="row">
            <div>
                <label>الاسم الأول</label>
                <input name="first_name" type="text" value="{{ old('first_name') }}" required>
            </div>
            <div>
                <label>الاسم الأخير</label>
                <input name="last_name" type="text" value="{{ old('last_name') }}" required>
            </div>
        </div>

        <label>رقم الهاتف</label>
        <input name="phone_number" type="text" value="{{ old('phone_number') }}" required>

        <label>البريد الإلكتروني</label>
        <input name="email" type="email" value="{{ old('email') }}" required>

        <button class="btn" type="submit">إنشاء رابط الدفع</button>
    </form>
</div>
</body>
</html>


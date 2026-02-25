@extends('layouts.master')

@section('title', 'عرض الدولة')

@section('content')
<div class="nxl-content">
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <h4 class="page-title">عرض الدولة</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('countries.index') }}">الدول</a></li>
                <li class="breadcrumb-item active" aria-current="page">عرض الدولة</li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-4">تفاصيل الدولة</h5>

            <div class="row mb-3">
                <label class="col-sm-3 fw-bold text-muted">الاسم بالعربية:</label>
                <div class="col-sm-9">
                    <span>{{ $country->name_ar }}</span>
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-3 fw-bold text-muted">الاسم بالإنجليزية:</label>
                <div class="col-sm-9">
                    <span>{{ $country->name_en }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('countries.edit', $country->id) }}" class="btn btn-primary">تعديل</a>
                <a href="{{ route('countries.index') }}" class="btn btn-secondary">رجوع</a>
            </div>
        </div>
    </div>
</div>
@endsection

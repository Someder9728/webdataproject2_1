<x-layouts::app.sidebar :title="'เพิ่มผู้เช่า'">

    <div class="container-fluid p-4">

        {{-- PAGE HEADER --}}
        <div class="mb-4">

            <a href="{{ route('tenants.index') }}" class="text-decoration-none text-secondary">
                <i class="bi bi-arrow-left me-1"></i>
                กลับไปหน้าผู้เช่า
            </a>

            <h1 class="h3 fw-bold mt-3 mb-1">
                เพิ่มผู้เช่า
            </h1>

            <p class="text-secondary mb-0">
                เพิ่มข้อมูลผู้เช่ารายใหม่เข้าสู่ระบบ
            </p>

        </div>

        {{-- VALIDATION ERRORS --}}
        @if ($errors->any())

        <div class="alert alert-danger">

            <div class="fw-semibold mb-2">
                กรุณาตรวจสอบข้อมูล
            </div>

            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>

        @endif

        {{-- FORM CARD --}}
        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">

                <form action="{{ route('tenants.store') }}" method="POST">

                    @csrf

                    {{-- NAME --}}
                    <div class="row g-4">

                        <div class="col-md-6">

                            <label for="t_Fname" class="form-label fw-semibold">
                                ชื่อ
                                <span class="text-danger">*</span>
                            </label>

                            <input type="text" id="t_Fname" name="t_Fname" value="{{ old('t_Fname') }}"
                                class="form-control" placeholder="กรอกชื่อ" required>

                        </div>

                        <div class="col-md-6">

                            <label for="t_Lname" class="form-label fw-semibold">
                                นามสกุล
                                <span class="text-danger">*</span>
                            </label>

                            <input type="text" id="t_Lname" name="t_Lname" value="{{ old('t_Lname') }}"
                                class="form-control" placeholder="กรอกนามสกุล" required>

                        </div>

                        {{-- PHONE --}}
                        <div class="col-md-6">

                            <label for="t_tel" class="form-label fw-semibold">
                                เบอร์โทรศัพท์
                                <span class="text-danger">*</span>
                            </label>

                            <input type="tel" id="t_tel" name="t_tel" value="{{ old('t_tel') }}" class="form-control"
                                placeholder="0812345678" maxlength="10" required>

                            <div class="form-text">
                                เบอร์โทรศัพท์ต้องมี 10 หลัก
                            </div>

                        </div>

                        {{-- EMAIL --}}
                        <div class="col-md-6">

                            <label for="t_mail" class="form-label fw-semibold">
                                อีเมล
                            </label>

                            <input type="email" id="t_mail" name="t_mail" value="{{ old('t_mail') }}"
                                class="form-control" placeholder="example@email.com">

                        </div>

                        {{-- ADDRESS --}}
                        <div class="col-12">

                            <label for="t_address" class="form-label fw-semibold">
                                ที่อยู่
                            </label>

                            <textarea id="t_address" name="t_address" rows="4" class="form-control"
                                placeholder="กรอกที่อยู่ของผู้เช่า">{{ old('t_address') }}</textarea>

                        </div>

                    </div>

                    {{-- BUTTONS --}}
                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">

                        <a href="{{ route('tenants.index') }}" class="btn btn-light border">
                            ยกเลิก
                        </a>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>
                            บันทึกข้อมูล
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-layouts::app.sidebar>
<x-layouts::app.sidebar :title="'แก้ไขผู้เช่า'">

    <div class="container-fluid p-4">

        {{-- PAGE HEADER --}}
        <div class="mb-4">

            <a href="{{ route('tenants.index') }}" class="text-decoration-none text-secondary">
                <i class="bi bi-arrow-left me-1"></i>
                กลับไปหน้าผู้เช่า
            </a>

            <h1 class="h3 fw-bold mt-3 mb-1">
                แก้ไขข้อมูลผู้เช่า
            </h1>

            <p class="text-secondary mb-0">
                แก้ไขข้อมูลของ
                <strong>
                    {{ $tenant->t_Fname }} {{ $tenant->t_Lname }}
                </strong>
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

        {{-- FORM --}}
        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">

                <form action="{{ route('tenants.update', $tenant) }}" method="POST">

                    @csrf
                    @method('PUT')

                    <div class="row g-4">

                        {{-- FIRST NAME --}}
                        <div class="col-md-6">

                            <label for="t_Fname" class="form-label fw-semibold">
                                ชื่อ
                                <span class="text-danger">*</span>
                            </label>

                            <input type="text" id="t_Fname" name="t_Fname"
                                value="{{ old('t_Fname', $tenant->t_Fname) }}" class="form-control @error('t_Fname') is-invalid @enderror"
                                maxlength="255" autocomplete="given-name" aria-invalid="@error('t_Fname') true @else false @enderror" required>
                            @error('t_Fname')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        </div>

                        {{-- LAST NAME --}}
                        <div class="col-md-6">

                            <label for="t_Lname" class="form-label fw-semibold">
                                นามสกุล
                                <span class="text-danger">*</span>
                            </label>

                            <input type="text" id="t_Lname" name="t_Lname"
                                value="{{ old('t_Lname', $tenant->t_Lname) }}" class="form-control @error('t_Lname') is-invalid @enderror"
                                maxlength="255" autocomplete="family-name" aria-invalid="@error('t_Lname') true @else false @enderror" required>
                            @error('t_Lname')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        </div>

                        {{-- PHONE --}}
                        <div class="col-md-6">

                            <label for="t_tel" class="form-label fw-semibold">
                                เบอร์โทรศัพท์
                                <span class="text-danger">*</span>
                            </label>

                            <input type="tel" id="t_tel" name="t_tel" value="{{ old('t_tel', $tenant->t_tel) }}"
                                class="form-control @error('t_tel') is-invalid @enderror" inputmode="numeric" autocomplete="tel"
                                pattern="[0-9]{10}" maxlength="10" aria-invalid="@error('t_tel') true @else false @enderror" required>
                            @error('t_tel')<div class="invalid-feedback">{{ $message }}</div>@enderror

                            <div class="form-text">
                                เบอร์โทรศัพท์ต้องมี 10 หลัก
                            </div>

                        </div>

                        {{-- EMAIL --}}
                        <div class="col-md-6">

                            <label for="t_mail" class="form-label fw-semibold">
                                อีเมล
                            </label>

                            <input type="email" id="t_mail" name="t_mail" value="{{ old('t_mail', $tenant->t_mail) }}"
                                class="form-control @error('t_mail') is-invalid @enderror" maxlength="255" autocomplete="email"
                                aria-invalid="@error('t_mail') true @else false @enderror">
                            @error('t_mail')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        </div>

                        {{-- ADDRESS --}}
                        <div class="col-12">

                            <label for="t_address" class="form-label fw-semibold">
                                ที่อยู่
                            </label>

                            <textarea id="t_address" name="t_address" rows="4" maxlength="2000"
                                class="form-control @error('t_address') is-invalid @enderror" aria-invalid="@error('t_address') true @else false @enderror">{{ old('t_address', $tenant->t_address) }}</textarea>
                            @error('t_address')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        </div>

                    </div>

                    {{-- BUTTONS --}}
                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">

                        <a href="{{ route('tenants.index') }}" class="btn btn-light border">
                            ยกเลิก
                        </a>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>
                            บันทึกการแก้ไข
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-layouts::app.sidebar>

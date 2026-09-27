<x-layouts::app.sidebar :title="'เพิ่มห้องพัก'">

    <div class="container-fluid p-4">

        {{-- Header --}}
        <div class="mb-4">

            <div class="d-flex align-items-center gap-3">

                <a href="{{ route('rooms.index') }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left"></i>
                </a>

                <div>
                    <h1 class="h3 fw-bold mb-1">
                        เพิ่มห้องพัก
                    </h1>

                    <p class="text-secondary mb-0">
                        เพิ่มข้อมูลห้องพักใหม่เข้าสู่ระบบ
                    </p>
                </div>

            </div>

        </div>

        {{-- Validation Errors --}}
        @if ($errors->any())

        <div class="alert alert-danger">

            <div class="fw-semibold mb-2">
                <i class="bi bi-exclamation-circle me-1"></i>
                กรุณาตรวจสอบข้อมูล
            </div>

            <ul class="mb-0">

                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

        @endif

        {{-- Form --}}
        <div class="card border-0 shadow-sm">

            <div class="card-body p-4">

                <form action="{{ route('rooms.store') }}" method="POST">

                    @csrf

                    {{-- Room Number --}}
                    <div class="mb-4">

                        <label for="r_number" class="form-label fw-semibold">
                            เลขห้อง
                            <span class="text-danger">*</span>
                        </label>

                        <input type="text" id="r_number" name="r_number" value="{{ old('r_number') }}"
                            class="form-control @error('r_number') is-invalid @enderror" placeholder="เช่น 101"
                            maxlength="20" required>

                        @error('r_number')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror

                    </div>

                    {{-- Floor --}}
                    <div class="mb-4">

                        <label for="r_floor" class="form-label fw-semibold">
                            ชั้น
                            <span class="text-danger">*</span>
                        </label>

                        <input type="number" id="r_floor" name="r_floor" value="{{ old('r_floor') }}"
                            class="form-control @error('r_floor') is-invalid @enderror" placeholder="เช่น 1" min="1"
                            required>

                        @error('r_floor')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror

                    </div>

                    {{-- Room Type --}}
                    <div class="mb-4">

                        <label for="r_type" class="form-label fw-semibold">
                            ประเภทห้อง
                            <span class="text-danger">*</span>
                        </label>

                        <select id="r_type" name="r_type" class="form-select @error('r_type') is-invalid @enderror"
                            required>

                            <option value="">
                                -- เลือกประเภทห้อง --
                            </option>

                            <option value="ห้องพัดลม" {{ old('r_type') === 'ห้องพัดลม' ? 'selected' : '' }}>
                                ห้องพัดลม
                            </option>

                            <option value="ห้องแอร์" {{ old('r_type') === 'ห้องแอร์' ? 'selected' : '' }}>
                                ห้องแอร์
                            </option>

                            <option value="ห้องแอร์ VIP" {{ old('r_type') === 'ห้องแอร์ VIP' ? 'selected' : '' }}>
                                ห้องแอร์ VIP
                            </option>

                        </select>

                        @error('r_type')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror

                    </div>

                    {{-- Price --}}
                    <div class="mb-4">

                        <label for="r_price" class="form-label fw-semibold">
                            ราคา/เดือน
                            <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">

                            <input type="number" id="r_price" name="r_price" value="{{ old('r_price') }}"
                                class="form-control @error('r_price') is-invalid @enderror" placeholder="เช่น 3500"
                                min="0" step="0.01" required>

                            <span class="input-group-text">
                                บาท
                            </span>

                        </div>

                        @error('r_price')
                        <div class="text-danger small mt-1">
                            {{ $message }}
                        </div>
                        @enderror

                    </div>

                    {{-- Status --}}
                    <div class="mb-4">

                        <label for="r_status" class="form-label fw-semibold">
                            สถานะห้อง
                            <span class="text-danger">*</span>
                        </label>

                        <select id="r_status" name="r_status"
                            class="form-select @error('r_status') is-invalid @enderror" required>

                            <option value="ว่าง" {{ old('r_status', 'ว่าง') === 'ว่าง' ? 'selected' : '' }}>
                                ว่าง
                            </option>

                            <option value="มีผู้พัก" {{ old('r_status') === 'มีผู้พัก' ? 'selected' : '' }}>
                                มีผู้พัก
                            </option>

                            <option value="ปิดปรับปรุง" {{ old('r_status') === 'ปิดปรับปรุง' ? 'selected' : '' }}>
                                ปิดปรับปรุง
                            </option>

                        </select>

                        @error('r_status')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror

                    </div>

                    {{-- Buttons --}}
                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">

                        <a href="{{ route('rooms.index') }}" class="btn btn-light border">
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
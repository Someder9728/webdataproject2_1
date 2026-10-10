<x-layouts::app.sidebar :title="'แก้ไขห้องพัก'">

    <div class="container-fluid p-4">

        {{-- Header --}}
        <div class="mb-4">

            <div class="d-flex align-items-center gap-3">

                <a href="{{ route('rooms.index') }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left"></i>
                </a>

                <div>
                    <h1 class="h3 fw-bold mb-1">
                        แก้ไขห้องพัก
                    </h1>

                    <p class="text-secondary mb-0">
                        แก้ไขข้อมูลห้อง {{ $room->r_name }}
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

                <form action="{{ route('rooms.update', $room) }}" method="POST">

                    @csrf
                    @method('PUT')

                    {{-- Room Number --}}
                    <div class="mb-4">

                        <label for="r_name" class="form-label fw-semibold">
                            เลขห้อง
                            <span class="text-danger">*</span>
                        </label>

                        <input type="text" id="r_name" name="r_name" value="{{ old('r_name', $room->r_name) }}"
                            class="form-control @error('r_name') is-invalid @enderror" maxlength="20" required>

                        @error('r_name')
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

                        <input type="number" id="r_floor" name="r_floor" value="{{ old('r_floor', $room->r_floor) }}"
                            class="form-control @error('r_floor') is-invalid @enderror" min="1" required>

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

                            <option value="ห้องพัดลม"
                                {{ old('r_type', $room->r_type) === 'ห้องพัดลม' ? 'selected' : '' }}>
                                ห้องพัดลม
                            </option>

                            <option value="ห้องแอร์"
                                {{ old('r_type', $room->r_type) === 'ห้องแอร์' ? 'selected' : '' }}>
                                ห้องแอร์
                            </option>

                            <option value="ห้องแอร์ VIP"
                                {{ old('r_type', $room->r_type) === 'ห้องแอร์ VIP' ? 'selected' : '' }}>
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

                        <label for="r_rent" class="form-label fw-semibold">
                            ราคา/เดือน
                            <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">

                            <input type="number" id="r_rent" name="r_rent" value="{{ old('r_rent', $room->r_rent) }}"
                                class="form-control @error('r_rent') is-invalid @enderror" min="0" step="0.01" required>

                            <span class="input-group-text">
                                บาท
                            </span>

                        </div>

                        @error('r_rent')
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

                        <input class="form-control" value="{{ $room->r_status === 'OCCUPIED' ? 'มีผู้พัก' : 'ว่าง' }}" readonly>
<p class="form-text">สถานะห้องเปลี่ยนผ่านการรับผู้เช่าเข้าและย้ายออก</p>

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
                            บันทึกการแก้ไข
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-layouts::app.sidebar>
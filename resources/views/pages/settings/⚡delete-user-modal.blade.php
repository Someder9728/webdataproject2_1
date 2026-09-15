<?php

use Livewire\Component;

new class extends Component {
    public function deleteUser(): void
    {
        abort(403, 'ระบบไม่อนุญาตให้ผู้ใช้ลบบัญชีตนเอง');
    }
};

?>

<div></div>
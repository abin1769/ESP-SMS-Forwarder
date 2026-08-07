<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Sms;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sms>
 */
class SmsFactory extends Factory
{
    protected $model = Sms::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'phone' => '+628' . $this->faker->numerify('##########'),
            'message' => $this->faker->randomElement([
                'Kode OTP rahasia Anda adalah: ' . $this->faker->numerify('######') . '. Jangan berikan ke siapapun.',
                'Info Tagihan Listrik PLN: Rp ' . number_format($this->faker->numberBetween(150000, 750000)) . ' telah dibayar.',
                'Alert Sensor Suhu: Suhu ruangan melebihi threshold 35°C saat ini.',
                'Halo, terima kasih telah mendaftar di layanan SMS Gateway ESP32.',
                'Peringatan Sistem: Tegangan aki ESP32 drop ke 3.4V!',
                'Transfer pulsa sebesar 25.000 sukses ke nomor 081234567890.',
            ]),
            'received_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'processed' => $this->faker->boolean(40),
        ];
    }
}

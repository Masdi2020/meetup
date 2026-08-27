<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Dimas',
                'username' => 'masdi2005',
                'password' => bcrypt('Kiwandim'),
                'force_change_password' => false,
            ],
            [
                'name' => 'admin',
                'username' => 'admin',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
                'force_change_password' => false,
            ],
            [
                'name' => 'display',
                'username' => 'display',
                'password' => bcrypt('display123'),
                'role' => 'display',
                'force_change_password' => false,
            ],
            ['name' => 'A. Hambali, SE', 'username' => 'hambali.achmad'],
            ['name' => 'Asha Jyoti Alfiyah, S.Tr.Stat.', 'username' => 'ashajyoti'],
            ['name' => 'Asih Pratiwi, S.ST', 'username' => 'apratiwi'],
            ['name' => 'Atin Praptiningsih', 'username' => 'atinprapti-pppk'],
            ['name' => 'Awika Yuliati Zukhrufah, S.Tr.Stat.', 'username' => 'awika.yuliati'],
            ['name' => 'Candra Kurniawan, S.Tr.Stat.', 'username' => 'candrakurnia'],
            ['name' => 'Devi Harnia Setyani, SST', 'username' => 'deviharnia'],
            ['name' => 'Dimas Nur Ramadhani, S.Tr.Stat.', 'username' => 'nur.ramadhani'],
            ['name' => 'Dwi Roswita', 'username' => 'dwi.roswita'],
            ['name' => 'Ika Mustika Sarah, SST', 'username' => 'ika.sarah'],
            ['name' => 'Imam Mulyono', 'username' => 'imammulyono-pppk'],
            ['name' => 'Inas Labibah Asahy, SST', 'username' => 'inas.labibah'],
            ['name' => 'Ince Zulfiana Hadi, S.Sos.', 'username' => 'ince.hadi'],
            ['name' => 'Isra Syukria Herida, S.Tr.Stat', 'username' => 'isra.syukria'],
            ['name' => 'Juli Yanti S., S.Si', 'username' => 'juli.ys'],
            ['name' => 'Loveandre Danang Handriyanto, SST', 'username' => 'loveandre'],
            ['name' => 'Yoanita Dwi Lestari, A.Md.Stat.', 'username' => 'yoanita.dl'],
            ['name' => 'Marinda Dama Prianto, S.Si', 'username' => 'marindassi'],
            ['name' => 'Mayuza Yolanda, S.Tr.Stat.', 'username' => 'mayuza.yolanda'],
            ['name' => 'Meliya Indri Sari, S.Si', 'username' => 'meliya_tifani'],
            ['name' => 'Winda Luvi Saputri, S.Tr.Stat.', 'username' => 'winda.luvi'],
            ['name' => 'Muhammad Rizqi Aulia Rahman, S.Tr.Stat.', 'username' => 'rizqi.aulia'],
            ['name' => 'Mustakim', 'username' => 'mustakimta'],
            ['name' => 'Nella Indriani, SST, M.Ec.Dev.', 'username' => 'nella.indriani'],
            ['name' => 'Ninik Mei Nurwati, SE.', 'username' => 'ninikmei'],
            ['name' => 'Oriza Satifa Putri Batubara, A.Md.Kb.N.', 'username' => 'oriza.putri'],
            ['name' => 'R. Agus Setyawan, SST', 'username' => 'agus.setya'],
            ['name' => 'Rahmi Hidayati, SST, M.E.K.K.', 'username' => 'rahmi.hidayati'],
            ['name' => 'Retno Ramadhani, S.Kom', 'username' => 'retnoramadhani'],
            ['name' => 'Ridha Asih, S.Kom', 'username' => 'ridha.asih'],
            ['name' => 'Risa Cahyanti, S.E.', 'username' => 'risa'],
            ['name' => 'Rizky Amalia, S.ST.', 'username' => 'rizkyamalia'],
            ['name' => 'Sabrina Do Miswa, S.Tr.Stat', 'username' => 'sabrina.domiswa'],
            ['name' => 'Syahrir Wahid, S.E.', 'username' => 'syahrir'],
            ['name' => 'Sugiono', 'username' => 'sugiono-pppk'],
            ['name' => 'Maria Andhini Resiana, SST', 'username' => 'maria.andhini'],
        ];

        foreach ($users as $user) {
            User::create([
                ...$user,
                'role' => $user['role'] ?? 'user',
                'force_change_password' => $user['force_change_password'] ?? true,
                'password' => $user['password'] ?? bcrypt(env('DEFAULT_PASSWORD')),
            ]);
        }
    }
}

<?php

use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //利用Faker实现大量数据模拟
        $faker = \Faker\Factory::create('zh_CN'); //创建一个faker对象
        for($i=0;$i<50;$i++){
            \Illuminate\Support\Facades\DB::table('teacher')->insert([
                'teacher_name'=>$faker->name,
                'teacher_phone'=>$faker->phoneNumber,
                'teacher_city'=>$faker->city,
                'teacher_address'=>$faker->address,
                'teacher_company'=>$faker->company,
                'teacher_email'=>$faker->email,
                'teacher_pic'=>$faker->imageUrl(),
                'teacher_desc'=>$faker->catchPhrase,
            ]);
        }
    }
}



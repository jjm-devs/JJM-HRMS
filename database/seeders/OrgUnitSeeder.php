<?php

namespace Database\Seeders;

use App\Models\DepartmentStream;
use App\Models\OrgUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrgUnitSeeder extends Seeder
{
    public function run(): void
    {
        $department = OrgUnit::query()->updateOrCreate(
            ['code' => 'PHED-ASSAM'],
            [
                'name' => 'PHED Assam',
                'type' => 'department',
                'status' => 'active',
            ],
        );

        $headOffice = OrgUnit::query()->updateOrCreate(
            ['code' => 'CE-PHED-ASSAM'],
            [
                'parent_id' => $department->id,
                'name' => 'Office Of the Chief Engineer(Water)',
                'type' => 'head_office',
                'status' => 'active',
            ],
        );

        $this->seedZones($headOffice);

        $missionDirector = OrgUnit::query()->updateOrCreate(
            ['code' => 'MISSION-DIRECTOR-WATER'],
            [
                'parent_id' => $headOffice->id,
                'name' => 'Office of the Mission Director',
                'type' => 'office',
                'status' => 'active',
            ],
        );

        $this->mapDepartmentStreams($headOffice, $missionDirector);
    }

    private function seedZones(OrgUnit $headOffice): void
    {
        $zones = [
            'Lower Assam Zone' => [
                'Guwahati Circle' => [
                    'Guwahati Division No-I' => [
                        'Guwahati Sub-Divn',
                        'Boko Sub-Divn',
                        'Hajo Sub-Divn',
                    ],
                    'Guwahati Division No-II' => [
                        'Water Works Sub-Divn',
                        'Dispur Sub-Divn',
                    ],
                    'Store & Workshop Divn' => [
                        'Store Sub-Divn',
                        'Mechanical Sub-Divn',
                    ],
                    'Rangia Division' => [
                        'Rangia Sub-Divn',
                        'Baihata Sub-Divn',
                    ],
                    'Goalpara Division' => [
                        'Goalpara Sub-Divn',
                    ],
                ],
                'Nalbari Circle' => [
                    'Nalbari Division' => [
                        'Nalbari Sub-Divn',
                    ],
                    'Belsor Division' => [
                        'Belsor Sub-Divn',
                    ],
                    'Barpeta Division' => [
                        'Barpeta Sub-Divn',
                    ],
                    'Bajali Division' => [
                        'Pathsala Sub-Divn',
                    ],
                    'Bongaigaon Division' => [
                        'Bongaigaon Sub-Divn',
                    ],
                    'South Salmara Mankachar' => [
                        'Hatsingimari Sub-Divn',
                    ],
                    'Dhubri Division' => [
                        'Dhubri Sub-Divn',
                        'Bilasipara Sub-Divn',
                    ],
                ],
                'Quality Control Circle' => [],
            ],
            'North Assam Zone' => [
                'Tezpur Circle' => [
                    'Tezpur Division No-I' => [
                        'Tezpur Sub-Divn-I',
                        'Tezpur Sub-Divn-II',
                    ],
                    'Tezpur Divn No-II' => [
                        'Dhekiajuli Sub-Divn',
                    ],
                    'Mangaldoi Division' => [
                        'Mangaldoi Sub-Divn',
                        'Orang Sub-Divn',
                    ],
                    'Biswanath Chariali Divn' => [
                        'Biswanath Sub-Divn',
                        'Gohpur Sub-Divn',
                    ],
                ],
                'Lakhimpur Circle' => [
                    'Lakhimpur Division' => [
                        'Lakhimpur Sub-Divn',
                        'Bihpuria Sub-Divn',
                    ],
                    'Ghilamara Division' => [
                        'Ghilamara Sub-Divn',
                        'Gogamukh Sub-Divn',
                    ],
                    'Dhemaji Division' => [
                        'Dhemaji Sub-Divn',
                        'Jonai Sub-Divn',
                    ],
                ],
            ],
            'Upper Assam Zone' => [
                'Nagaon Circle' => [
                    'Nagaon Division' => [
                        'Nagaon Sub-Divn',
                        'Kathiatoli Sub-Divn',
                    ],
                    'Morigaon Division' => [
                        'Morigaon Divn',
                    ],
                    'Dhing Division' => [
                        'Dhing Sub-Divn',
                    ],
                    'Hojai Division' => [
                        'Hojai Sub-Divn',
                        'Lumding Sub-Divn',
                    ],
                    'Kaliabor Division' => [
                        'Kaliabor Sub-Divn',
                        'Jakhalabondha Sub-Divn',
                    ],
                ],
                'Jorhat Circle' => [
                    'Jorhat Division' => [
                        'Jorhat Sub-Divn',
                        'Titabor Sub-Divn',
                    ],
                    'Bokakhat Division' => [
                        'Bokakhat Sub-Divn',
                        'Dergaon Sub-Divn',
                    ],
                    'Majuli Division' => [
                        'Kamalabari Sub-Divn',
                    ],
                    'Golaghat Division' => [
                        'Golaghat Sub-Divn',
                        'Sarupathar Sub-Divn',
                    ],
                ],
                'Dibrugarh Circle' => [
                    'Sivsagar Division' => [
                        'Sivsagar Sub-Divn',
                        'Amguri Sub-Divn',
                        'Nazira Sub-Divn',
                    ],
                    'Nazira Division' => [
                        'Sonari Sub-Divn',
                    ],
                    'Dibrugarh Division' => [
                        'Dibrugarh Sub-Divn',
                        'Naharkatia Sub-Divn',
                        'Moran Sub-Divn',
                    ],
                    'Tinsukia Division' => [
                        'Tinsukia Sub-Divn',
                        'Digboi Sub-Divn',
                    ],
                ],
            ],
            'Barak Valley Zone' => [
                'Cachar Circle' => [
                    'Silchar Division No-I' => [
                        'Silchar Sub-Divn No-I',
                    ],
                    'Silchar Division No-II' => [
                        'Silchar Sub-Divn-II',
                        'Lakhipur Sub-Divn',
                    ],
                ],
                'Hailakandi Circle' => [
                    'Hailakandi Division' => [
                        'Hailakandi Sub-Divn',
                        'Katlicherra Sub-Divn',
                    ],
                    'Karimganj Division' => [
                        'Karimganj Sub-Divn',
                        'Patharkandi Sub-Divn',
                        'Badarpur Sub-Divn',
                    ],
                ],
            ],
            'KAAC Zone' => [
                'Diphu(Rural) Division' => [
                    'Diphu (Rural) Sub-Divn',
                    'Chowkihola Sub-Divn',
                ],
                'Diphu(Urban) Division' => [
                    'DiphuTown (C) Sub-Divn',
                    'Diphu Town (M) Sub-Divn',
                ],
                'Howraghat Division' => [
                    'Howraghat Sub-Divn.',
                    'Dokmoka Sub-Divn',
                ],
                'Hamren Division' => [
                    'Hamren Sub-Divn',
                    'Kheroni Sub-Divn',
                ],
                'Ulukunchi' => [
                    'Ulukunchi',
                    'Amtereng',
                ],
            ],
            'DHAC(Haflong) Zone' => [
                'Haflong Division' => [
                    'Haflong (Urban) Sub-Divn',
                    'Haflong(Rural) Sub-Divn',
                    'Gunjung Sub-Divn',
                ],
                'Maibang Division' => [
                    'Maibang Sub-Divn',
                    'Mahur Sub-Divn',
                ],
                'Umrangsoo Division' => [
                    'Umrangsoo Sub-Divn.',
                    'Dyungmukh Sub-Divn.',
                ],
            ],
            'BTAD Zone' => [
                'Kokrajhar Division No-I' => [
                    'Kokrajhar Sub-Divn No-I',
                    'Dotoma Sub-Divn',
                ],
                'Kokrajhar Division No-II' => [
                    'Kokrajhar Sub-Divn. No-II',
                    'Bijni Sub-Divn',
                ],
                'Gossaigaon Division' => [
                    'Gossaigaon Sub-Divn',
                    'Parbatjhora Sub-Divn',
                ],
                'Baska Division' => [
                    'Mushalpur Sub-Divn',
                    'Tamulpur Sub-Divn',
                ],
                'Tangla Division' => [
                    'Tangla Sub-Divn',
                    'Udalguri Sub-Divn',
                    'Kalaigaon Sub-Divn',
                ],
            ],
        ];

        foreach ($zones as $zoneName => $children) {
            $zone = $this->createUnit($zoneName, 'zone', $headOffice, $zoneName);

            if ($this->hasCircleChildren($children)) {
                $this->seedCircleBasedZone($zone, $children);

                continue;
            }

            $this->seedDirectDivisions($zone, $children);
        }
    }

    /**
     * @param  array<string, array<string, array<int, string>>|array<int, string>>  $circles
     */
    private function seedCircleBasedZone(OrgUnit $zone, array $circles): void
    {
        foreach ($circles as $circleName => $divisions) {
            $circle = $this->createUnit($circleName, 'circle', $zone);

            $this->seedDirectDivisions($circle, $divisions);
        }
    }

    /**
     * @param  array<string, array<int, string>>  $divisions
     */
    private function seedDirectDivisions(OrgUnit $parent, array $divisions): void
    {
        foreach ($divisions as $divisionName => $subDivisions) {
            $division = $this->createUnit($divisionName, 'division', $parent);

            foreach ($subDivisions as $subDivisionName) {
                $this->createUnit($subDivisionName, 'sub_division', $division);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $children
     */
    private function hasCircleChildren(array $children): bool
    {
        foreach (array_keys($children) as $name) {
            if (str_contains($name, 'Circle')) {
                return true;
            }
        }

        return false;
    }

    private function createUnit(string $name, string $type, OrgUnit $parent, ?string $codeName = null): OrgUnit
    {
        $code = $type === 'zone'
            ? $this->makeCode($codeName ?? $name)
            : $this->makeCode($parent->code.'-'.$name);

        return OrgUnit::query()->updateOrCreate(
            ['code' => $code],
            [
                'parent_id' => $parent->id,
                'name' => $name,
                'type' => $type,
                'status' => 'active',
            ],
        );
    }

    private function mapDepartmentStreams(OrgUnit $phedOffice, OrgUnit $missionDirector): void
    {
        $phedStream = DepartmentStream::query()
            ->where('code', 'PHED')
            ->where('status', 'active')
            ->first();

        $nonPhedStreamIds = DepartmentStream::query()
            ->where('code', '!=', 'PHED')
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        $phedStreamIds = $phedStream ? [$phedStream->id] : [];

        OrgUnit::query()
            ->select(['id', 'name'])
            ->cursor()
            ->each(function (OrgUnit $orgUnit) use ($phedOffice, $missionDirector, $phedStreamIds, $nonPhedStreamIds): void {
                if ($orgUnit->is($phedOffice)) {
                    $orgUnit->departmentStreams()->sync($phedStreamIds);

                    return;
                }

                if ($orgUnit->is($missionDirector)) {
                    $orgUnit->departmentStreams()->sync($nonPhedStreamIds);

                    return;
                }

                $orgUnit->departmentStreams()->sync($nonPhedStreamIds);
            });
    }

    private function makeCode(string $value): string
    {
        return Str::upper(Str::slug($value));
    }
}

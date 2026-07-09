<?php
namespace Database\Seeders;

use App\Models\LabResult;
use App\Models\Patient;
use App\Models\User;
use App\Services\FunctionalMedicineInterpreter;
use App\Services\NarrativeBuilder;
use Illuminate\Database\Seeder;

/**
 * End-to-end sample data for the FM report engine.
 *
 * Names are ANONYMISED (PDPA: do not seed real patient PII into dev/test DBs).
 * The lab VALUES are representative clinical profiles so you can exercise the
 * full scoring + narrative + report path without needing source PDFs.
 *
 * Run:  php artisan db:seed --class=Database\\Seeders\\FmSampleSeeder
 */
class FmSampleSeeder extends Seeder
{
    public function run(): void
    {
        $engine = app(FunctionalMedicineInterpreter::class);
        $narr   = app(NarrativeBuilder::class);

        $user = User::first() ?? User::factory()->create([
            'name' => 'Demo Clinician', 'email' => 'demo@example.test',
        ]);
        $clinicId = $user->clinic_id ?? 1;

        foreach ($this->samples() as $s) {
            $patient = Patient::create([
                'clinic_id' => $clinicId,
                'name'      => $s['name'],   // anonymised
                'sex'       => $s['sex'],
                'age'       => $s['age'],
            ]);
            $patient->consents()->create([
                'purpose' => 'health_processing', 'basis' => 'explicit_consent',
                'granted' => true, 'notice_version' => config('pdpa.notice_version'),
                'granted_at' => now(),
            ]);

            $report = $patient->labReports()->create([
                'uploaded_by' => $user->id, 'reviewed_by' => $user->id,
                'reviewed_at' => now(), 'status' => 'reviewed',
                'lab_no' => $s['lab_no'], 'panel' => 'GSH3 (demo)',
                'draft_values' => $s['values'],
            ]);

            $res = $engine->interpret($s['values'], $s['sex']);
            foreach ($res['markers'] as $m) {
                LabResult::create([
                    'lab_report_id' => $report->id, 'marker_key' => $m['key'],
                    'value' => $m['value'], 'unit' => $m['unit'], 'status' => $m['status'],
                ]);
            }
            $report->interpretation()->create([
                'engine_version' => FunctionalMedicineInterpreter::ENGINE_VERSION,
                'ratios' => $res['ratios'], 'critical' => $res['critical'],
                'narrative' => $narr->build($res),
            ]);

            $this->command?->info("Seeded {$s['name']} — ".count($res['critical']).' critical finding(s).');
        }
    }

    /** Anonymised names, representative clinical value profiles. */
    private function samples(): array
    {
        return [
            ['name' => 'Demo Patient A', 'sex' => 'F', 'age' => 60, 'lab_no' => 'DEMO-A', 'values' => [
                'haemoglobin'=>14.2,'mcv'=>90,'rdw'=>13.7,'serum_iron'=>18.7,'urea'=>4.9,'creatinine'=>64,'egfr'=>95,'uric_acid'=>364,'sodium'=>139,'potassium'=>4.8,'magnesium'=>0.91,'alp'=>87,'ast'=>18,'alt'=>22,'ggt'=>60,'fib4'=>0.82,'total_cholesterol'=>5.74,'hdl'=>1.52,'non_hdl'=>4.22,'ldl'=>3.55,'triglycerides'=>1.45,'homocysteine'=>16.1,'hs_crp'=>5.1,'glucose'=>5.2,'hba1c'=>5.4,'insulin'=>14.2,'tsh'=>1.44,'free_t4'=>17.30,'free_t3'=>3.6,'testosterone'=>1.9,'dhea_s'=>30.9,'cortisol_am'=>107.4,'vitamin_d'=>66.6,
            ]],
            ['name' => 'Demo Patient B', 'sex' => 'F', 'age' => 38, 'lab_no' => 'DEMO-B', 'values' => [
                'haemoglobin'=>13.3,'mcv'=>80,'rdw'=>12.8,'serum_iron'=>12.0,'urea'=>3.3,'creatinine'=>70,'egfr'=>98,'uric_acid'=>297,'sodium'=>135,'potassium'=>3.9,'magnesium'=>0.82,'alp'=>105,'ast'=>27,'alt'=>32,'ggt'=>36,'fib4'=>0.43,'total_cholesterol'=>4.45,'hdl'=>1.33,'non_hdl'=>3.12,'ldl'=>2.63,'triglycerides'=>1.06,'homocysteine'=>13.7,'hs_crp'=>20.4,'glucose'=>11.4,'hba1c'=>10.8,'insulin'=>15.2,'tsh'=>1.02,'free_t4'=>15.90,'free_t3'=>4.0,'vitamin_d'=>12.8,
            ]],
            ['name' => 'Demo Patient C', 'sex' => 'M', 'age' => 56, 'lab_no' => 'DEMO-C', 'values' => [
                'haemoglobin'=>16.5,'mcv'=>91,'rdw'=>12.7,'serum_iron'=>17.8,'urea'=>4.6,'creatinine'=>90,'egfr'=>86,'uric_acid'=>428,'sodium'=>138,'potassium'=>4.6,'magnesium'=>0.93,'alp'=>91,'ast'=>18,'alt'=>17,'ggt'=>9,'fib4'=>0.78,'total_cholesterol'=>6.08,'hdl'=>1.41,'non_hdl'=>4.67,'ldl'=>4.05,'triglycerides'=>1.34,'homocysteine'=>13.4,'hs_crp'=>1.3,'glucose'=>4.7,'hba1c'=>5.6,'insulin'=>4.3,'tsh'=>1.16,'free_t4'=>16.70,'free_t3'=>3.5,'vitamin_d'=>19.0,
            ]],
            ['name' => 'Demo Patient D', 'sex' => 'M', 'age' => 56, 'lab_no' => 'DEMO-D', 'values' => [
                'haemoglobin'=>14.5,'mcv'=>68,'rdw'=>15.4,'serum_iron'=>11.6,'urea'=>4.3,'creatinine'=>88,'egfr'=>89,'uric_acid'=>368,'sodium'=>141,'potassium'=>5.0,'magnesium'=>0.93,'alp'=>105,'ast'=>21,'alt'=>21,'ggt'=>32,'fib4'=>0.88,'total_cholesterol'=>4.94,'hdl'=>1.64,'non_hdl'=>3.30,'ldl'=>3.02,'triglycerides'=>0.60,'homocysteine'=>13.7,'hs_crp'=>20.3,'glucose'=>5.8,'hba1c'=>5.9,'insulin'=>5.5,'tsh'=>0.92,'free_t4'=>15.20,'free_t3'=>4.0,'lh'=>7.21,'estradiol'=>130.75,'prolactin'=>161.35,'testosterone'=>21.2,'dhea_s'=>145.0,'cortisol_am'=>225.4,'vitamin_d'=>58.4,
            ]],
            ['name' => 'Demo Patient E', 'sex' => 'M', 'age' => 37, 'lab_no' => 'DEMO-E', 'values' => [
                'haemoglobin'=>15.3,'mcv'=>86,'rdw'=>13.5,'serum_iron'=>14.8,'urea'=>3.8,'creatinine'=>86,'egfr'=>103,'uric_acid'=>413,'sodium'=>138,'potassium'=>4.9,'magnesium'=>0.81,'alp'=>41,'ast'=>28,'alt'=>38,'ggt'=>52,'fib4'=>0.69,'total_cholesterol'=>5.08,'hdl'=>0.95,'non_hdl'=>4.13,'ldl'=>3.25,'triglycerides'=>1.92,'homocysteine'=>16.9,'hs_crp'=>1.0,'glucose'=>5.8,'hba1c'=>5.6,'insulin'=>13.7,'tsh'=>1.58,'free_t4'=>13.00,'free_t3'=>4.6,'lh'=>3.91,'estradiol'=>95.78,'prolactin'=>151.94,'testosterone'=>7.7,'dhea_s'=>324.3,'cortisol_am'=>250.4,'vitamin_d'=>19.0,
            ]],
            ['name' => 'Demo Patient F', 'sex' => 'F', 'age' => 53, 'lab_no' => 'DEMO-F', 'values' => [
                'haemoglobin'=>12.5,'mcv'=>87,'rdw'=>12.3,'serum_iron'=>12.4,'urea'=>4.2,'creatinine'=>53,'egfr'=>107,'uric_acid'=>268,'sodium'=>129,'potassium'=>4.4,'magnesium'=>0.74,'alp'=>64,'ast'=>25,'alt'=>41,'ggt'=>95,'fib4'=>0.81,'total_cholesterol'=>6.88,'hdl'=>1.33,'non_hdl'=>5.55,'ldl'=>4.12,'triglycerides'=>6.08,'homocysteine'=>10.7,'hs_crp'=>20.3,'glucose'=>17.3,'hba1c'=>14.7,'insulin'=>7.8,'tsh'=>1.54,'free_t4'=>14.90,'free_t3'=>3.2,'vitamin_d'=>12.0,
            ]],
        ];
    }
}

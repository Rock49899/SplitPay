<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use App\Models\Student;
use App\Models\StudyLevel;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StudentImportController extends Controller
{
    use FiltersByAnnexe;
    
    /**
     * Télécharger le template Excel vierge (en français)
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'A1' => ['text' => 'Matricule', 'help' => 'Matricule unique de l\'étudiant (ex: STU2026001)'],
            'B1' => ['text' => 'Prénom', 'help' => 'Prénom de l\'étudiant'],
            'C1' => ['text' => 'Nom', 'help' => 'Nom de famille de l\'étudiant'],
            'D1' => ['text' => 'Email', 'help' => 'Adresse email (format: nom@domaine.com)'],
            'E1' => ['text' => 'Téléphone', 'help' => 'Numéro de téléphone (+242 06 XXX XXXX)'],
            'F1' => ['text' => 'Niveau d\'étude', 'help' => 'Nom du niveau d\'étude (ex: Licence 1, Master 2, Terminale)'],
            'G1' => ['text' => 'Spécialisation', 'help' => 'Nom de la spécialisation (ex: Informatique, Gestion, Comptabilité)'],
        ];
        
        // Style de l'en-tête
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'], // Indigo
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        
        // Remplir les en-têtes
        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header['text']);
            $sheet->getComment($cell)->getText()->createTextRun($header['help']);
        }
        
        // Appliquer le style à la ligne d'en-tête
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);
        
        // Ajuster la largeur des colonnes
        $columnWidths = [
            'A' => 18, // Matricule
            'B' => 20, // Prénom
            'C' => 20, // Nom
            'D' => 30, // Email
            'E' => 20, // Téléphone
            'F' => 25, // Niveau d'étude
            'G' => 25, // Spécialisation
        ];
        
        foreach ($columnWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        
        // Ajouter quelques lignes d'exemple
        $sheet->setCellValue('A2', 'STU2026001');
        $sheet->setCellValue('B2', 'Jean');
        $sheet->setCellValue('C2', 'Dupont');
        $sheet->setCellValue('D2', 'jean.dupont@example.com');
        $sheet->setCellValue('E2', '+242 06 123 4567');
        $sheet->setCellValue('F2', 'Licence 1');
        $sheet->setCellValue('G2', 'Informatique');
        
        $sheet->setCellValue('A3', 'STU2026002');
        $sheet->setCellValue('B3', 'Marie');
        $sheet->setCellValue('C3', 'Mbemba');
        $sheet->setCellValue('D3', 'marie.mbemba@example.com');
        $sheet->setCellValue('E3', '+242 06 234 5678');
        $sheet->setCellValue('F3', 'Master 1');
        $sheet->setCellValue('G3', 'Gestion');
        
        $sheet->setCellValue('A4', 'STU2026003');
        $sheet->setCellValue('B4', 'Paul');
        $sheet->setCellValue('C4', 'Okemba');
        $sheet->setCellValue('D4', 'paul.okemba@example.com');
        $sheet->setCellValue('E4', '+242 06 345 6789');
        $sheet->setCellValue('F4', 'Licence 3');
        $sheet->setCellValue('G4', 'Comptabilité');
        
        // Style des lignes d'exemple (italique gris)
        $sheet->getStyle('A2:G4')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
        ]);
        
        // Créer le fichier
        $writer = new Xlsx($spreadsheet);
        $filename = 'modele_import_etudiants_' . date('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        
        $writer->save($tempFile);
        
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
    
    /**
     * Preview des données avant import
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120', // Max 5MB
        ]);
        
        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray(null, true, true, true);
            
            // Retirer l'en-tête
            $headers = array_shift($data);
            
            $preview = [];
            $errors = [];
            $rowNumber = 2; // Commence à 2 (ligne 1 = en-tête)
            
            foreach ($data as $row) {
                // Ignorer les lignes vides
                if (empty(array_filter($row))) {
                    $rowNumber++;
                    continue;
                }
                
                $studentData = [
                    'matricule' => trim($row['A'] ?? ''),
                    'first_name' => trim($row['B'] ?? ''),
                    'last_name' => trim($row['C'] ?? ''),
                    'email' => trim($row['D'] ?? ''),
                    'phone' => trim($row['E'] ?? ''),
                    'study_level_label' => trim($row['F'] ?? ''), // Libellé du niveau d'étude
                    'specialization_label' => trim($row['G'] ?? ''), // Libellé de la spécialisation
                ];
                
                // Validation
                $validator = $this->validateStudentData($studentData, $rowNumber);
                
                if ($validator->fails()) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'data' => $studentData,
                        'errors' => $validator->errors()->all(),
                    ];
                } else {
                    $preview[] = [
                        'row' => $rowNumber,
                        'data' => $studentData,
                        'status' => 'valid',
                    ];
                }
                
                $rowNumber++;
            }
            
            return response()->json([
                'total_rows' => count($preview) + count($errors),
                'valid_rows' => count($preview),
                'invalid_rows' => count($errors),
                'preview' => array_slice($preview, 0, 10), // Première 10 lignes valides
                'errors' => $errors,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la lecture du fichier',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Importer les étudiants
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);
        
        $activeAnnexeId = $this->getActiveAnnexeId();
        
        // Si super admin institution, demander de sélectionner une annexe
        if ($this->isSuperAdminInstitution() && !$activeAnnexeId) {
            return response()->json([
                'message' => 'Veuillez sélectionner une annexe avant d\'importer des étudiants',
            ], 400);
        }
        
        if (!$activeAnnexeId) {
            return response()->json([
                'message' => 'Annexe active non définie',
            ], 400);
        }
        
        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray(null, true, true, true);
            
            // Retirer l'en-tête
            array_shift($data);
            
            $imported = 0;
            $failed = [];

            // Précharger le catalogue académique de l'annexe active
            // et indexer avec des clés normalisées (insensible à la casse / espaces)
            $studyLevelsByNormalizedLabel = StudyLevel::where('annexe_id', $activeAnnexeId)
                ->get()
                ->mapWithKeys(function (StudyLevel $level) {
                    return [
                        $this->normalizeAcademicLabel($level->label) => $level,
                        $this->normalizeAcademicLabel($level->code)  => $level,
                    ];
                });

            $specializationsByNormalizedLabel = Specialization::where('annexe_id', $activeAnnexeId)
                ->get()
                ->mapWithKeys(function (Specialization $specialization) {
                    return [
                        $this->normalizeAcademicLabel($specialization->label) => $specialization,
                        $this->normalizeAcademicLabel($specialization->code)  => $specialization,
                    ];
                });
            
            DB::beginTransaction();
            
            try {
                foreach ($data as $index => $row) {
                    $rowNumber = $index + 2;
                    
                    // Ignorer les lignes vides
                    if (empty(array_filter($row))) continue;
                    
                    $studentData = [
                        'matricule' => trim($row['A'] ?? ''),
                        'first_name' => trim($row['B'] ?? ''),
                        'last_name' => trim($row['C'] ?? ''),
                        'email' => trim($row['D'] ?? ''),
                        'phone' => trim($row['E'] ?? ''),
                        'study_level_label' => trim($row['F'] ?? ''),
                        'specialization_label' => trim($row['G'] ?? ''),
                    ];
                    
                    $validator = $this->validateStudentData($studentData, $rowNumber);
                    
                    if ($validator->fails()) {
                        $failed[] = [
                            'row' => $rowNumber,
                            'data' => $studentData,
                            'errors' => $validator->errors()->all(),
                        ];
                        continue;
                    }
                    
                    // Chercher le niveau d'étude par libellé (si fourni)
                    $studyLevelId = null;
                    if (!empty($studentData['study_level_label'])) {
                        $studyLevel = $studyLevelsByNormalizedLabel->get(
                            $this->normalizeAcademicLabel($studentData['study_level_label'])
                        );
                        
                        if (!$studyLevel) {
                            $failed[] = [
                                'row' => $rowNumber,
                                'data' => $studentData,
                                'errors' => ["Niveau d'étude '{$studentData['study_level_label']}' non trouvé"],
                            ];
                            continue;
                        }
                        
                        $studyLevelId = $studyLevel->id;
                    }
                    
                    // Chercher la spécialisation par libellé (si fourni)
                    $specializationId = null;
                    if (!empty($studentData['specialization_label'])) {
                        $specialization = $specializationsByNormalizedLabel->get(
                            $this->normalizeAcademicLabel($studentData['specialization_label'])
                        );
                        
                        if (!$specialization) {
                            $failed[] = [
                                'row' => $rowNumber,
                                'data' => $studentData,
                                'errors' => ["Spécialisation '{$studentData['specialization_label']}' non trouvée"],
                            ];
                            continue;
                        }
                        
                        $specializationId = $specialization->id;
                    }
                    
                    // Créer l'étudiant SANS study_level_id (car ce champ n'existe pas sur Student)
                    $student = Student::create([
                        'matricule' => $studentData['matricule'],
                        'first_name' => $studentData['first_name'],
                        'last_name' => $studentData['last_name'],
                        'email' => $studentData['email'],
                        'phone' => $studentData['phone'],
                        'specialization_id' => $specializationId,
                        'annexe_id' => $activeAnnexeId,
                        'status' => 'active',
                    ]);
                    
                    // Créer un enrollment si un niveau d'étude est fourni
                    if ($studyLevelId) {
                        // Récupérer l'année scolaire active (format YYYY-YYYY)
                        $currentYear = now()->year;
                        $month = now()->month;
                        // Si on est entre janvier et août, on est dans l'année N-1/N
                        // Si on est entre septembre et décembre, on est dans l'année N/N+1
                        $schoolYear = $month >= 9 
                            ? "{$currentYear}-" . ($currentYear + 1)
                            : ($currentYear - 1) . "-{$currentYear}";
                        
                        // Résoudre le barème de frais
                        $fee = \App\Models\LevelFee::resolve(
                            $studyLevelId,
                            $specializationId,
                            $schoolYear,
                            (string) $activeAnnexeId
                        );
                        
                        // Créer l'inscription
                        \App\Models\Enrollment::create([
                            'student_id'     => $student->id,
                            'level_fee_id'   => $fee?->id,
                            'tuition_amount' => $fee?->tuition_amount ?? 0,
                            'amount_paid'    => 0,
                            'school_year'    => $schoolYear,
                            'status'         => 'active',
                        ]);
                    }
                    
                    $imported++;
                }
                
                DB::commit();
                
                return response()->json([
                    'message' => "Import terminé avec succès",
                    'imported' => $imported,
                    'failed' => count($failed),
                    'failed_rows' => $failed,
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'import',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Exporter les étudiants 
     */
    public function export(Request $request)
    {
        $query = Student::with(['specialization', 'studyLevel']);
        $query = $this->scopeByUserAnnexes($query);
        
        $students = $query->get();
        
        if ($students->isEmpty()) {
            return response()->json([
                'message' => 'Aucun étudiant à exporter',
            ], 404);
        }
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // En-têtes 
        $headers = [
            'A1' => 'Matricule',
            'B1' => 'Prénom',
            'C1' => 'Nom',
            'D1' => 'Email',
            'E1' => 'Téléphone',
            'F1' => 'Niveau d\'étude',
            'G1' => 'Spécialisation',
        ];
        
        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }
        
        // Style en-tête
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        
        // Données
        $row = 2;
        foreach ($students as $student) {
            $sheet->setCellValue('A' . $row, $student->matricule);
            $sheet->setCellValue('B' . $row, $student->first_name);
            $sheet->setCellValue('C' . $row, $student->last_name);
            $sheet->setCellValue('D' . $row, $student->email);
            $sheet->setCellValue('E' . $row, $student->phone);
            $sheet->setCellValue('F' . $row, $student->studyLevel?->label ?? '');
            $sheet->setCellValue('G' . $row, $student->specialization?->label ?? '');
            $row++;
        }
        
        // Auto-size colonnes
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $filename = 'export_etudiants_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        
        $writer->save($tempFile);
        
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
    
    /**
     * Valider les données d'un étudiant
     */
    private function validateStudentData(array $data, int $rowNumber)
    {
        return Validator::make($data, [
            'matricule' => 'required|string|max:50|unique:students,matricule',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:students,email',
            'phone' => 'required|string|max:50',
            'study_level_label' => 'nullable|string|max:255',
            'specialization_label' => 'nullable|string|max:255',
        ], [
            'matricule.required' => "Le matricule est obligatoire (ligne $rowNumber)",
            'matricule.unique' => "Le matricule existe déjà en base de données (ligne $rowNumber)",
            'first_name.required' => "Le prénom est obligatoire (ligne $rowNumber)",
            'last_name.required' => "Le nom est obligatoire (ligne $rowNumber)",
            'email.required' => "L'email est obligatoire (ligne $rowNumber)",
            'email.email' => "Le format de l'email est invalide (ligne $rowNumber)",
            'email.unique' => "L'email existe déjà en base de données (ligne $rowNumber)",
            'phone.required' => "Le téléphone est obligatoire (ligne $rowNumber)",
        ]);
    }

    /**
     * Normalise un libellé académique pour comparaison robuste:
     * - trim
     * - collapse des espaces multiples
     * - minuscules
     */
    private function normalizeAcademicLabel(?string $value): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
        return Str::lower($collapsed);
    }
}

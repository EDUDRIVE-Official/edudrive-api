param(
    [string]$DraftPath = (Join-Path $PSScriptRoot '../docs/product/BORRADOR-BLOQUES-CAMINO-PASAJERO-v1.json'),
    [string]$SourcePath = (Join-Path $PSScriptRoot '../docs/product/review-camino-pasajero-source-2026-09-20.json')
)
$ErrorActionPreference = 'Stop'
function Assert-Editorial([bool]$Condition, [string]$Message) {
    if (-not $Condition) { throw $Message }
}
$draft = Get-Content -LiteralPath $DraftPath -Raw -Encoding UTF8 | ConvertFrom-Json
$source = Get-Content -LiteralPath $SourcePath -Raw -Encoding UTF8 | ConvertFrom-Json
Assert-Editorial ($draft.status -eq 'editorial_draft' -and $draft.importable -eq $false -and $draft.production_changes -eq $false) 'Draft must not claim publication/importability.'
Assert-Editorial ($draft.lessons.Count -eq 5) 'Expected five lessons.'
Assert-Editorial (@($draft.lessons.lesson_id | Sort-Object -Unique).Count -eq 5) 'Duplicate lesson IDs.'
Assert-Editorial ($draft.audience_proposal.approved -eq $false) 'Audience must remain a proposal.'
Assert-Editorial ($draft.evidence_policy.demonstrates_mastery -eq $false -and $draft.evidence_policy.recording_implemented -eq $false) 'Evidence must not claim mastery or implemented recording.'
foreach ($review in $draft.review.PSObject.Properties) {
    Assert-Editorial ($review.Value -eq 'pending') ('Unexpected approval: ' + $review.Name)
}
$positions = @(0, 0, 0)
$scenarios = 0
foreach ($lesson in $draft.lessons) {
    $origin = @($source.lessons | Where-Object id -eq $lesson.lesson_id)
    Assert-Editorial ($origin.Count -eq 1) ('Missing source: ' + $lesson.lesson_id)
    $origin = $origin[0]
    Assert-Editorial ($origin.course -eq $lesson.course_code -and $origin.title -eq $lesson.title -and $origin.design.version -eq $lesson.source_design_version) 'Source identity/version changed.'
    Assert-Editorial ($lesson.stage -eq 'pending_review' -and $lesson.requires_guardian -eq $true) 'Stage/guardian guard changed.'
    foreach ($field in @('objective', 'instruction', 'adult', 'visual', 'alignment')) {
        Assert-Editorial (-not [string]::IsNullOrWhiteSpace($lesson.$field)) ('Missing editorial field: ' + $field)
    }
    Assert-Editorial ($lesson.transfer.observe.Count -ge 2 -and $lesson.transfer.case -and $lesson.transfer.prompt) 'Missing transfer task.'
    $sourceBlocks = @($origin.blocks | Where-Object type -eq 'scenario')
    $newBlocks = @($lesson.blocks | Where-Object type -eq 'scenario')
    Assert-Editorial ($sourceBlocks.Count -eq $newBlocks.Count) 'Scenario count changed.'
    Assert-Editorial (@($newBlocks.payload.title | Sort-Object -Unique).Count -eq $newBlocks.Count) 'Duplicate scenario title.'
    foreach ($block in $newBlocks) {
        $payload = $block.payload
        $original = @($sourceBlocks | Where-Object { $_.payload.title -eq $payload.title })
        Assert-Editorial ($original.Count -eq 1) ('Scene title lost: ' + $payload.title)
        $beforeIds = @($original[0].payload.choices.id | Sort-Object)
        $afterIds = @($payload.choices.id | Sort-Object)
        Assert-Editorial (($beforeIds -join '|') -ceq ($afterIds -join '|')) 'Choice IDs changed.'
        Assert-Editorial ($payload.choices.Count -eq 3 -and @($afterIds | Select-Object -Unique).Count -eq 3) 'Invalid choice count/duplicates.'
        Assert-Editorial (@($payload.choices | Where-Object correct -eq $true).Count -eq 1) 'Expected exactly one correct answer.'
        $beforeCorrect = @($original[0].payload.choices | Where-Object correct -eq $true)[0].id
        $afterCorrect = @($payload.choices | Where-Object correct -eq $true)[0].id
        Assert-Editorial ($beforeCorrect -ceq $afterCorrect) 'Correct choice identity changed.'
        Assert-Editorial ($payload.accessible_text -ceq ($payload.context + ' ' + $payload.prompt)) 'Accessible text differs from question.'
        foreach ($choice in $payload.choices) {
            Assert-Editorial (-not [string]::IsNullOrWhiteSpace($choice.label) -and -not [string]::IsNullOrWhiteSpace($choice.feedback)) 'Missing label/feedback.'
            Assert-Editorial ($choice.feedback -notlike '*conserva protección y permite responder*' -and $choice.feedback -notlike '*depende de que nada cambie*') 'Generic feedback remains.'
        }
        for ($i = 0; $i -lt 3; $i++) { if ($payload.choices[$i].correct) { $positions[$i]++ } }
        $scenarios++
    }
}
Assert-Editorial ($scenarios -eq 10 -and ($positions -join ',') -eq '4,3,3') 'Unexpected answer distribution.'
[pscustomobject]@{ Result = 'PASS'; Lessons = 5; Scenarios = $scenarios; A = $positions[0]; B = $positions[1]; C = $positions[2]; Scope = 'Editorial structure only; not human approval or visual QA' }

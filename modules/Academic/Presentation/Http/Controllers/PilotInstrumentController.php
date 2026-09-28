<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Infrastructure\Services\PilotDraftWorkspace;
use Modules\Academic\Infrastructure\Services\PilotInstrumentRuns;
use Modules\Academic\Infrastructure\Services\PilotNormativeAlignment;
use Modules\Academic\Infrastructure\Services\PilotReadiness;
use Modules\Academic\Infrastructure\Services\PilotVisualCandidates;
use Modules\Academic\Infrastructure\Services\PilotVisualReviewers;
use Modules\Academic\Infrastructure\Services\PilotVisualReviews;
use Modules\Notification\Application\Services\MailDeliveryReadiness;

final class PilotInstrumentController
{
    private function guard(): void
    {
        abort_unless(
            app()->environment(['local', 'testing']) || config('pilot.instruments_enabled', false),
            404,
        );
    }

    private function owner(Request $request): string
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        return (string) $user->getAuthIdentifier();
    }

    public function index(): View
    {
        $this->guard();

        return view('courses.pilot-instrument', ['run' => null]);
    }

    public function unit(): View
    {
        $this->guard();
        $source = file_get_contents(resource_path('curriculum/p912/crossing-unit.v0.1.json'));
        if ($source === false) {
            throw new \RuntimeException('No se pudo leer la unidad P912.');
        }

        return view('courses.pilot-unit', ['unit' => json_decode($source, true, 512, JSON_THROW_ON_ERROR)]);
    }

    public function visual(): View
    {
        $this->guard();

        return view('courses.pilot-visual');
    }

    public function van(): View
    {
        $this->guard();

        return view('courses.pilot-van');
    }

    public function descent(): View
    {
        $this->guard();

        return view('courses.pilot-descent');
    }

    public function barrier(): View
    {
        $this->guard();

        return view('courses.pilot-barrier');
    }

    public function visualSequence(): View
    {
        $this->guard();

        return view('courses.pilot-visual-sequence');
    }

    public function visualReview(Request $request, PilotVisualReviews $reviews, PilotVisualReviewers $reviewers): View
    {
        $this->guard();

        return view('courses.pilot-visual-review', [
            'reviews' => $reviews->forReviewer($this->owner($request)),
            'designation' => $reviewers->activeForUser($this->owner($request)),
            'sceneVersion' => PilotVisualReviews::VERSION,
        ]);
    }

    public function visualReviewGuide(): View
    {
        $this->guard();

        return view('courses.pilot-visual-review-guide', [
            'sceneVersion' => PilotVisualReviews::VERSION,
        ]);
    }

    public function storeVisualReview(Request $request, PilotVisualReviews $reviews, PilotVisualReviewers $reviewers): RedirectResponse
    {
        $this->guard();
        $owner = $this->owner($request);
        $designation = $reviewers->activeForUser($owner);
        abort_unless($designation !== null, 403, 'Tu cuenta no tiene una designación activa para revisar estas escenas.');
        $data = $request->validate([
            'scene' => ['required', Rule::in(PilotVisualReviews::SCENES)],
            'reviewed_on' => ['required', 'date'],
            'criteria' => ['required', 'array'],
            'criteria.*' => ['required', 'boolean'],
            'findings' => ['nullable', 'required_if:status,changes_required', 'string', 'max:5000'],
            'status' => ['required', Rule::in(PilotVisualReviews::STATUSES)],
        ]);
        foreach (PilotVisualReviews::CRITERIA as $criterion) {
            abort_unless(array_key_exists($criterion, $data['criteria']), 422);
        }
        $data['specialty'] = $designation['specialty'];
        $reviews->save($owner, $data, $designation['event_id']);

        return redirect()->route('pilot-instruments.visual-review')->with('status', 'Revisión interna guardada. No constituye aprobación final.');
    }

    public function visualReviewers(PilotVisualReviewers $reviewers): View
    {
        $this->guard();

        return view('courses.pilot-visual-reviewers', [
            'designations' => $reviewers->all(),
            'designationHistory' => $reviewers->history(),
            'eligibleUsers' => $reviewers->eligibleUsers(),
        ]);
    }

    public function storeVisualReviewer(Request $request, PilotVisualReviewers $reviewers): RedirectResponse
    {
        $this->guard();
        $data = $request->validate([
            'user_id' => ['required', 'uuid'],
            'specialty' => ['required', Rule::in(PilotVisualReviews::SPECIALTIES)],
            'organization' => ['required', 'string', 'max:180'],
            'qualification' => ['required', 'string', 'max:2000'],
            'evidence_reference' => ['required', 'string', 'max:500'],
            'designated_on' => ['required', 'date'],
            'active' => ['required', 'boolean'],
        ]);
        $reviewers->save($data, $this->owner($request));

        return redirect()->route('pilot-instruments.visual-reviewers')->with('status', 'Designación interna guardada. No certifica credenciales profesionales.');
    }

    public function visualReviewSummary(PilotVisualReviews $reviews): View
    {
        $this->guard();

        return view('courses.pilot-visual-review-summary', [
            'coverage' => $reviews->coverage(),
            'sceneVersion' => PilotVisualReviews::VERSION,
        ]);
    }

    public function visualReadiness(PilotReadiness $readiness): View
    {
        $this->guard();

        return view('courses.pilot-visual-readiness', [
            'readiness' => $readiness->status(),
        ]);
    }

    public function pilotProtocol(PilotReadiness $readiness): View
    {
        $this->guard();

        return view('courses.pilot-protocol', [
            'readiness' => $readiness->status(),
        ]);
    }

    public function pilotForms(PilotReadiness $readiness): View
    {
        $this->guard();

        return view('courses.pilot-forms', [
            'readiness' => $readiness->status(),
            'sceneVersion' => PilotVisualReviews::VERSION,
        ]);
    }

    public function pilotDossier(PilotReadiness $readiness): View
    {
        $this->guard();

        return view('courses.pilot-dossier', [
            'readiness' => $readiness->status(),
            'sceneVersion' => PilotVisualReviews::VERSION,
        ]);
    }

    public function normativeAlignment(PilotNormativeAlignment $alignment): View
    {
        $this->guard();

        return view('courses.pilot-normative-alignment', [
            'rows' => $alignment->rows(),
            'sources' => $alignment->sources(),
            'verifiedOn' => PilotNormativeAlignment::VERIFIED_ON,
        ]);
    }

    public function institutionalReviewPackage(PilotReadiness $readiness): View
    {
        $this->guard();

        return view('courses.pilot-institutional-review-package', [
            'readiness' => $readiness->status(),
            'sceneVersion' => PilotVisualReviews::VERSION,
            'preparedOn' => now()->settings(['locale' => 'es'])->translatedFormat('j \d\e F \d\e Y'),
        ]);
    }

    public function institutionalSubmission(PilotReadiness $readiness, PilotNormativeAlignment $alignment): View
    {
        $this->guard();

        return view('courses.pilot-institutional-submission', [
            'readiness' => $readiness->status(),
            'normativeRows' => $alignment->rows(),
            'normativeSources' => $alignment->sources(),
            'normativeVerifiedOn' => PilotNormativeAlignment::VERIFIED_ON,
            'sceneVersion' => PilotVisualReviews::VERSION,
            'preparedOn' => now()->settings(['locale' => 'es'])->translatedFormat('j \d\e F \d\e Y'),
        ]);
    }

    public function reviewerOnboarding(PilotReadiness $readiness): View
    {
        $this->guard();

        return view('courses.pilot-reviewer-onboarding', [
            'readiness' => $readiness->status(),
            'sceneVersion' => PilotVisualReviews::VERSION,
        ]);
    }

    public function reviewCoordination(
        PilotVisualReviewers $reviewers,
        MailDeliveryReadiness $mailDeliveryReadiness,
    ): View {
        $this->guard();

        return view('courses.pilot-review-coordination', [
            'reviewers' => $reviewers->coordination(),
            'sceneVersion' => PilotVisualReviews::VERSION,
            'mailDelivery' => $mailDeliveryReadiness->status(),
        ]);
    }

    public function visualCandidates(PilotVisualCandidates $candidates, PilotVisualReviews $reviews): View
    {
        $this->guard();

        return view('courses.pilot-visual-candidates', [
            'bindings' => $candidates->bindings(),
            'coverage' => $reviews->coverage(),
            'draftLessons' => $candidates->draftLessons(),
            'sceneVersion' => PilotVisualReviews::VERSION,
        ]);
    }

    public function storeVisualCandidate(Request $request, PilotVisualCandidates $candidates): RedirectResponse
    {
        $this->guard();
        $data = $request->validate([
            'scene' => ['required', Rule::in(PilotVisualReviews::SCENES)],
            'lesson_id' => ['required', 'uuid'],
        ]);
        $candidates->assign($data['scene'], $data['lesson_id'], $this->owner($request));

        return redirect()->route('pilot-instruments.visual-candidates')->with('status', 'Candidatura guardada. El contenido de la lección no fue modificado.');
    }

    public function createPilotDraft(PilotDraftWorkspace $workspace): RedirectResponse
    {
        $this->guard();
        $result = $workspace->ensure();
        $message = $result['created']
            ? 'Espacio borrador P912 creado con cuatro lecciones. Ningún curso publicado fue modificado.'
            : 'El espacio borrador P912 ya existía; no se creó un duplicado.';

        return redirect()->route('pilot-instruments.visual-candidates')->with('status', $message);
    }

    public function start(Request $request, PilotInstrumentRuns $runs): RedirectResponse
    {
        $this->guard();
        $data = $request->validate(['form' => ['required', Rule::in(PilotInstrumentRuns::FORMS)], 'synthetic_only' => ['accepted']]);
        $id = $runs->start($this->owner($request), $data['form']);

        return redirect()->route('pilot-instruments.show', $id);
    }

    public function show(string $runId, Request $request, PilotInstrumentRuns $runs): View
    {
        $this->guard();

        return view('courses.pilot-instrument', ['run' => $runs->read($runId, $this->owner($request))]);
    }

    public function update(string $runId, Request $request, PilotInstrumentRuns $runs): RedirectResponse
    {
        $this->guard();
        $data = $request->validate([
            'revision' => ['required', 'integer', 'min:0'], 'action' => ['required', Rule::in(['response', 'hint', 'finish'])],
            'item_id' => ['nullable', 'string', 'max:10'], 'response' => ['nullable', 'string', 'max:2000'],
            'access_support' => ['nullable', 'string', 'max:500'], 'content_help' => ['nullable', 'string', 'max:500'],
        ]);
        $runs->append($runId, $this->owner($request), (int) $data['revision'], $data['action'], $data);

        return redirect()->route('pilot-instruments.show', $runId);
    }
}

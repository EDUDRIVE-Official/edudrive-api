<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Certification\Domain\Aggregates\Certificate;
use Modules\Certification\Domain\Repositories\CertificateRepository;
use Modules\Certification\Domain\ValueObjects\CertificateId;
use Modules\Certification\Domain\ValueObjects\ValidationCode;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

function persistedCertificateWebUser(string $name = 'Estudiante Certificado'): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: $name,
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user;
}

function persistedCertificateWebCourse(): Course
{
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('CERT-'.Str::upper(Str::random(6))),
        title: CourseTitle::fromString('Seguridad vial avanzada'),
    );
    app(CourseRepository::class)->save($course);

    return $course;
}

function persistedWebCertificate(User $user, Course $course): Certificate
{
    $certificate = Certificate::create(
        id: CertificateId::fromString((string) Str::uuid()),
        userId: $user->id(),
        courseId: $course->id()->value(),
        validationCode: ValidationCode::generate(),
    );
    app(CertificateRepository::class)->save($certificate);

    return $certificate;
}

it('permite verificar públicamente un certificado por su código', function (): void {
    /** @var TestCase $this */
    $certificate = persistedWebCertificate(persistedCertificateWebUser(), persistedCertificateWebCourse());

    $this->get('/verificar-certificado?code='.$certificate->validationCode()->value())
        ->assertOk()
        ->assertSeeText('Seguridad vial avanzada')
        ->assertSeeText('Válido')
        ->assertSeeText($certificate->validationCode()->value());
});

it('muestra un mensaje cuando el código público no existe', function (): void {
    /** @var TestCase $this */
    $this->get('/verificar-certificado?code=AAAA-BBBB-CCCC')
        ->assertOk()
        ->assertSeeText('No encontramos un certificado con ese código.');
});

it('requiere autenticación para consultar los certificados propios', function (): void {
    /** @var TestCase $this */
    $this->get('/mis-certificados')->assertRedirect(route('login'));
});

it('muestra los certificados del usuario autenticado y no los de terceros', function (): void {
    /** @var TestCase $this */
    $user = persistedCertificateWebUser();
    $course = persistedCertificateWebCourse();
    $own = persistedWebCertificate($user, $course);
    $other = persistedWebCertificate(persistedCertificateWebUser('Otro estudiante'), $course);
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/mis-certificados')
        ->assertOk()
        ->assertSeeText($own->validationCode()->value())
        ->assertDontSeeText($other->validationCode()->value());
});

it('muestra un certificado imprimible solo a su titular', function (): void {
    /** @var TestCase $this */
    $user = persistedCertificateWebUser('María Camino Seguro');
    $course = persistedCertificateWebCourse();
    $certificate = persistedWebCertificate($user, $course);

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web')
        ->get(route('certificates.show', $certificate->id()->value()))
        ->assertOk()
        ->assertSeeText('María Camino Seguro')
        ->assertSeeText('Seguridad vial avanzada')
        ->assertSeeText($certificate->validationCode()->value())
        ->assertSeeText('Imprimir o guardar como PDF');

    $other = persistedCertificateWebUser('Otra persona');
    $this->actingAs(UserModel::query()->findOrFail($other->id()), 'web')
        ->get(route('certificates.show', $certificate->id()->value()))
        ->assertNotFound();
});

it('permite a un administrador emitir, consultar y revocar un certificado', function (): void {
    /** @var TestCase $this */
    $this->actingAs(actingAsSuperAdminUser(), 'web');
    $user = persistedCertificateWebUser();
    $course = persistedCertificateWebCourse();

    $response = $this->post('/admin/certificados', [
        'user_id' => $user->id(),
        'course_id' => $course->id()->value(),
    ]);

    $certificate = app(CertificateRepository::class)->findByUserAndCourse($user->id(), $course->id()->value());
    expect($certificate)->not->toBeNull();
    $response->assertRedirect(route('certificates.admin.search', ['certificate_id' => $certificate->id()->value()]));

    $this->get('/admin/certificados?certificate_id='.$certificate->id()->value())
        ->assertOk()
        ->assertSeeText($certificate->validationCode()->value())
        ->assertSeeText('Revocar');

    $this->post('/admin/certificados/'.$certificate->id()->value().'/revocar', ['reason' => 'Corrección administrativa'])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(app(CertificateRepository::class)->findById($certificate->id())?->status()->value)->toBe('revoked');
});

it('rechaza la gestión administrativa sin permisos', function (): void {
    /** @var TestCase $this */
    $user = persistedCertificateWebUser();
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/admin/certificados')->assertForbidden();
    $this->post('/admin/certificados', [
        'user_id' => (string) Str::uuid(),
        'course_id' => (string) Str::uuid(),
    ])->assertForbidden();
});

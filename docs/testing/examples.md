---
title: Exemplos de Testes
description: Exemplos práticos de testes no projeto
version: 1.0.0
last_updated: 2025-10-26
tags: [testing, examples, phpunit, feature-tests, unit-tests]
---

# 🧪 Exemplos de Testes

## Visão Geral

Este documento contém exemplos práticos de testes utilizados no projeto.

## 🌐 Feature Tests (Testes de API)

### Exemplo 1: Testar Endpoint de Listagem

```php
<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use Heart\User\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ListUsersTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_it_lists_users_successfully(): void
    {
        // Arrange: Criar usuários de teste
        UserModel::factory()->count(5)->create();
        
        // Act: Fazer requisição
        $response = $this->getJson('/api/users');
        
        // Assert: Verificar resposta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'username',
                        'level',
                        'xp',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'total',
                ]
            ])
            ->assertJson([
                'success' => true,
            ]);
        
        // Verificar que retornou 5 usuários
        $this->assertCount(5, $response->json('data'));
    }
    
    public function test_it_paginates_users_correctly(): void
    {
        // Arrange: Criar 25 usuários
        UserModel::factory()->count(25)->create();
        
        // Act: Página 1 (15 items por página)
        $response = $this->getJson('/api/users?page=1&per_page=15');
        
        // Assert
        $response->assertStatus(200);
        $this->assertCount(15, $response->json('data'));
        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(2, $response->json('meta.last_page'));
    }
    
    public function test_it_filters_users_by_level(): void
    {
        // Arrange
        UserModel::factory()->create(['level' => 10]);
        UserModel::factory()->create(['level' => 20]);
        UserModel::factory()->create(['level' => 30]);
        
        // Act: Filtrar usuários com level >= 20
        $response = $this->getJson('/api/users?filter[level_min]=20');
        
        // Assert: Deve retornar apenas 2 usuários
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }
}
```

### Exemplo 2: Testar Autenticação

```php
<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Heart\User\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_get_profile_when_authenticated(): void
    {
        // Arrange: Criar e autenticar usuário
        $user = UserModel::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        
        // Act: Fazer requisição autenticada
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/users/me');
        
        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'username' => $user->username,
                ]
            ]);
    }
    
    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        // Act: Tentar acessar rota protegida sem token
        $response = $this->getJson('/api/users/me');
        
        // Assert: Deve retornar 401
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                ]
            ]);
    }
    
    public function test_invalid_token_returns_unauthorized(): void
    {
        // Act: Token inválido
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-here',
        ])->getJson('/api/users/me');
        
        // Assert
        $response->assertStatus(401);
    }
}
```

### Exemplo 3: Testar Validação

```php
<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateUserValidationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_username_is_required(): void
    {
        // Act: Enviar dados sem username
        $response = $this->postJson('/api/users', [
            'email' => 'test@example.com',
        ]);
        
        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }
    
    public function test_email_must_be_valid(): void
    {
        // Act: Email inválido
        $response = $this->postJson('/api/users', [
            'username' => 'testuser',
            'email' => 'invalid-email',
        ]);
        
        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
    
    public function test_username_must_be_unique(): void
    {
        // Arrange: Criar usuário existente
        $existingUser = UserModel::factory()->create([
            'username' => 'danielhe4rt'
        ]);
        
        // Act: Tentar criar com mesmo username
        $response = $this->postJson('/api/users', [
            'username' => 'danielhe4rt',
            'email' => 'new@example.com',
        ]);
        
        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }
    
    /**
     * @dataProvider invalidUsernameProvider
     */
    public function test_username_validation(string $username, string $expectedError): void
    {
        $response = $this->postJson('/api/users', [
            'username' => $username,
            'email' => 'test@example.com',
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }
    
    public static function invalidUsernameProvider(): array
    {
        return [
            'too short' => ['ab', 'min:3'],
            'too long' => [str_repeat('a', 51), 'max:50'],
            'with spaces' => ['user name', 'alpha_dash'],
        ];
    }
}
```

## 🔬 Unit Tests (Testes Unitários)

### Exemplo 1: Testar Entity

```php
<?php

namespace Tests\Unit\User\Domain\Entities;

use PHPUnit\Framework\TestCase;
use Heart\User\Domain\Entities\User;
use Heart\User\Domain\ValueObjects\Email;
use Heart\User\Domain\ValueObjects\Level;

class UserTest extends TestCase
{
    public function test_it_creates_user_with_valid_data(): void
    {
        // Arrange & Act
        $user = new User(
            id: 1,
            username: 'danielhe4rt',
            email: new Email('daniel@example.com'),
            level: new Level(10, 5000)
        );
        
        // Assert
        $this->assertEquals(1, $user->getId());
        $this->assertEquals('danielhe4rt', $user->getUsername());
        $this->assertEquals('daniel@example.com', $user->getEmail()->getValue());
        $this->assertEquals(10, $user->getLevel()->getValue());
    }
    
    public function test_it_adds_experience_correctly(): void
    {
        // Arrange
        $user = new User(
            id: 1,
            username: 'test',
            email: new Email('test@example.com'),
            level: new Level(1, 0)
        );
        
        // Act
        $user->addExperience(100);
        
        // Assert
        $this->assertEquals(100, $user->getXp());
    }
    
    public function test_it_levels_up_when_reaching_required_xp(): void
    {
        // Arrange
        $user = new User(
            id: 1,
            username: 'test',
            email: new Email('test@example.com'),
            level: new Level(1, 90)
        );
        
        // Act: Adicionar XP suficiente para subir de nível
        $user->addExperience(20); // 90 + 20 = 110 (level 1 requer 100 XP)
        
        // Assert
        $this->assertEquals(2, $user->getLevel()->getValue());
        $this->assertEquals(10, $user->getXp()); // XP restante
    }
}
```

### Exemplo 2: Testar Value Object

```php
<?php

namespace Tests\Unit\User\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Heart\User\Domain\ValueObjects\Email;
use Heart\User\Domain\Exceptions\InvalidEmailException;

class EmailTest extends TestCase
{
    public function test_it_creates_valid_email(): void
    {
        // Act
        $email = new Email('test@example.com');
        
        // Assert
        $this->assertEquals('test@example.com', $email->getValue());
    }
    
    public function test_it_throws_exception_for_invalid_email(): void
    {
        // Assert
        $this->expectException(InvalidEmailException::class);
        
        // Act
        new Email('invalid-email');
    }
    
    public function test_it_compares_emails_correctly(): void
    {
        // Arrange
        $email1 = new Email('test@example.com');
        $email2 = new Email('test@example.com');
        $email3 = new Email('other@example.com');
        
        // Assert
        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }
    
    /**
     * @dataProvider invalidEmailProvider
     */
    public function test_invalid_emails(string $invalidEmail): void
    {
        $this->expectException(InvalidEmailException::class);
        new Email($invalidEmail);
    }
    
    public static function invalidEmailProvider(): array
    {
        return [
            ['invalid'],
            ['@example.com'],
            ['test@'],
            ['test @example.com'],
            [''],
        ];
    }
}
```

### Exemplo 3: Testar Use Case com Mocks

```php
<?php

namespace Tests\Unit\User\Application\UseCases;

use PHPUnit\Framework\TestCase;
use Heart\User\Application\UseCases\FindUserByIdUseCase;
use Heart\User\Domain\Entities\User;
use Heart\User\Domain\Repositories\UserRepositoryInterface;
use Heart\User\Domain\ValueObjects\Email;
use Heart\User\Domain\ValueObjects\Level;

class FindUserByIdUseCaseTest extends TestCase
{
    public function test_it_finds_user_by_id(): void
    {
        // Arrange: Criar mock do repository
        $repository = $this->createMock(UserRepositoryInterface::class);
        
        $expectedUser = new User(
            id: 1,
            username: 'test',
            email: new Email('test@example.com'),
            level: new Level(1, 0)
        );
        
        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($expectedUser);
        
        $useCase = new FindUserByIdUseCase($repository);
        
        // Act
        $result = $useCase->execute(1);
        
        // Assert
        $this->assertSame($expectedUser, $result);
    }
    
    public function test_it_returns_null_when_user_not_found(): void
    {
        // Arrange
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);
        
        $useCase = new FindUserByIdUseCase($repository);
        
        // Act
        $result = $useCase->execute(999);
        
        // Assert
        $this->assertNull($result);
    }
}
```

## 🎭 Testes com Factories

### Exemplo: Usar Factory

```php
<?php

namespace Tests\Feature\Badges;

use Tests\TestCase;
use Heart\User\Infrastructure\Models\UserModel;
use Heart\Badges\Infrastructure\Models\BadgeModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssignBadgeTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_it_assigns_badge_to_user(): void
    {
        // Arrange: Usar factories
        $user = UserModel::factory()->create();
        $badge = BadgeModel::factory()->create(['rarity' => 'common']);
        
        // Act
        $response = $this->postJson("/api/users/{$user->id}/badges", [
            'badge_id' => $badge->id
        ]);
        
        // Assert
        $response->assertStatus(201);
        
        $this->assertDatabaseHas('characters_badges', [
            'user_id' => $user->id,
            'badge_id' => $badge->id,
        ]);
    }
    
    public function test_user_with_high_level_can_unlock_epic_badge(): void
    {
        // Arrange: Factory com estado específico
        $user = UserModel::factory()
            ->highLevel() // Custom state
            ->create();
        
        $badge = BadgeModel::factory()
            ->epic()
            ->create(['required_level' => 30]);
        
        // Act
        $response = $this->postJson("/api/users/{$user->id}/badges", [
            'badge_id' => $badge->id
        ]);
        
        // Assert
        $response->assertStatus(201);
    }
}
```

## 🗄️ Testes de Banco de Dados

### Exemplo: Testar Queries

```php
<?php

namespace Tests\Feature\Ranking;

use Tests\TestCase;
use Heart\User\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RankingQueryTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_ranking_orders_users_by_xp_descending(): void
    {
        // Arrange: Criar usuários com XP diferentes
        $user1 = UserModel::factory()->create(['xp' => 1000, 'username' => 'user1']);
        $user2 = UserModel::factory()->create(['xp' => 3000, 'username' => 'user2']);
        $user3 = UserModel::factory()->create(['xp' => 2000, 'username' => 'user3']);
        
        // Act
        $response = $this->getJson('/api/ranking?sort=xp&order=desc');
        
        // Assert: Ordem correta
        $data = $response->json('data');
        $this->assertEquals('user2', $data[0]['username']); // 3000 XP
        $this->assertEquals('user3', $data[1]['username']); // 2000 XP
        $this->assertEquals('user1', $data[2]['username']); // 1000 XP
    }
    
    public function test_it_counts_users_correctly(): void
    {
        // Arrange
        UserModel::factory()->count(15)->create();
        
        // Act
        $count = UserModel::count();
        
        // Assert
        $this->assertEquals(15, $count);
    }
}
```

## 🔗 Recursos Relacionados

- [Guia de Testes](./guide.md) - Estrutura e configuração
- [Code Standards](../development/code-standards.md) - Padrões de código

---

> 💡 **Dica**: Use factories para dados de teste ao invés de criar manualmente.

> ✅ **Boa Prática**: Siga o padrão AAA (Arrange, Act, Assert) em todos os testes.

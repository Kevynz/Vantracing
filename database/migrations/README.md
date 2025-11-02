# Database Migrations

Este diretório contém scripts SQL incrementais para evolução do banco de dados do VanTracing.

## Ordem de execução

1. **001_init.sql** – Schema inicial (tabelas usuarios, motoristas, responsaveis, criancas, password_resets, driver_locations, schema_migrations)
2. **002_profile_split.sql** – Normalização: remove cpf/cnh/data_nascimento de usuarios e migra para motoristas/responsaveis

## Como aplicar

Execute os scripts SQL na ordem numérica contra seu banco MySQL. Cada script é idempotente (pode rodar múltiplas vezes sem efeito colateral).

```bash
mysql -u root -p vantracing_db < database/migrations/001_init.sql
mysql -u root -p vantracing_db < database/migrations/002_profile_split.sql
```

## Migration Tracking

Uma tabela `schema_migrations` registra qual versão foi aplicada. Verifique assim:

```sql
SELECT * FROM schema_migrations ORDER BY applied_at;
```

---

## Database Migrations (EN)

This directory contains incremental SQL scripts for VanTracing database evolution.

## Execution order

1. **001_init.sql** – Initial schema (usuarios, motoristas, responsaveis, criancas, password_resets, driver_locations, schema_migrations tables)
2. **002_profile_split.sql** – Normalization: removes cpf/cnh/data_nascimento from usuarios and migrates to motoristas/responsaveis

## How to apply

Run SQL scripts in numerical order against your MySQL database. Each script is idempotent (can run multiple times safely).

```bash
mysql -u root -p vantracing_db < database/migrations/001_init.sql
mysql -u root -p vantracing_db < database/migrations/002_profile_split.sql
```

## Migration Tracking

A `schema_migrations` table records which version was applied. Check like this:

```sql
SELECT * FROM schema_migrations ORDER BY applied_at;
```

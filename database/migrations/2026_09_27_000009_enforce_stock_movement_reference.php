<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER stock_movements_valid_stock_reference_insert
                BEFORE INSERT ON stock_movements
                FOR EACH ROW
                WHEN NOT (
                    (NEW.material_id IS NOT NULL AND NEW.ink_stock_id IS NULL)
                    OR (NEW.material_id IS NULL AND NEW.ink_stock_id IS NOT NULL)
                )
                BEGIN
                    SELECT RAISE(ABORT, 'Exactly one stock item must be selected.');
                END;
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER stock_movements_valid_stock_reference_update
                BEFORE UPDATE OF material_id, ink_stock_id ON stock_movements
                FOR EACH ROW
                WHEN NOT (
                    (NEW.material_id IS NOT NULL AND NEW.ink_stock_id IS NULL)
                    OR (NEW.material_id IS NULL AND NEW.ink_stock_id IS NOT NULL)
                )
                BEGIN
                    SELECT RAISE(ABORT, 'Exactly one stock item must be selected.');
                END;
            SQL);

            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE stock_movements
            ADD CONSTRAINT stock_movements_exactly_one_stock_item
            CHECK (
                (material_id IS NOT NULL AND ink_stock_id IS NULL)
                OR (material_id IS NULL AND ink_stock_id IS NOT NULL)
            )
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS stock_movements_valid_stock_reference_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS stock_movements_valid_stock_reference_update');

            return;
        }

        DB::statement('ALTER TABLE stock_movements DROP CHECK stock_movements_exactly_one_stock_item');
    }
};

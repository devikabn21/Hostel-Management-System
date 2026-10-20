CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_admin_username_casefold
    ON admin_users (lower(username));
CREATE UNIQUE INDEX IF NOT EXISTS uq_admin_email_casefold
    ON admin_users (lower(email));

CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL,
    usn TEXT NOT NULL UNIQUE,
    department TEXT NOT NULL,
    semester INTEGER NOT NULL CHECK (semester BETWEEN 1 AND 12),
    phone TEXT NOT NULL,
    email TEXT NOT NULL,
    gender TEXT NOT NULL CHECK (gender IN ('Female', 'Male', 'Other')),
    address TEXT NOT NULL,
    guardian_name TEXT NOT NULL,
    guardian_phone TEXT NOT NULL,
    registration_date TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_students_name ON students (full_name);
CREATE INDEX IF NOT EXISTS idx_students_department_semester ON students (department, semester);

CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_number TEXT NOT NULL UNIQUE,
    room_type TEXT NOT NULL CHECK (room_type IN ('Single', 'Double', 'Triple', 'Dormitory')),
    floor INTEGER NOT NULL CHECK (floor BETWEEN 0 AND 50),
    capacity INTEGER NOT NULL CHECK (capacity BETWEEN 1 AND 50),
    status TEXT NOT NULL DEFAULT 'available' CHECK (status IN ('available', 'maintenance')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS room_allocations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    room_id INTEGER NOT NULL,
    bed_number INTEGER NOT NULL CHECK (bed_number > 0),
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'vacated')),
    allocated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    vacated_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (room_id) REFERENCES rooms (id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_allocations_active_student
    ON room_allocations (student_id) WHERE status = 'active';
CREATE UNIQUE INDEX IF NOT EXISTS uq_allocations_active_bed
    ON room_allocations (room_id, bed_number) WHERE status = 'active';
CREATE INDEX IF NOT EXISTS idx_allocations_student_history
    ON room_allocations (student_id, allocated_at);
CREATE INDEX IF NOT EXISTS idx_allocations_room_history
    ON room_allocations (room_id, allocated_at);

CREATE TRIGGER IF NOT EXISTS trg_allocations_capacity_insert
BEFORE INSERT ON room_allocations
WHEN NEW.status = 'active'
BEGIN
    SELECT CASE
        WHEN NEW.bed_number > (SELECT capacity FROM rooms WHERE id = NEW.room_id)
        THEN RAISE(ABORT, 'bed number exceeds room capacity')
        WHEN (SELECT COUNT(*) FROM room_allocations WHERE room_id = NEW.room_id AND status = 'active') >= (SELECT capacity FROM rooms WHERE id = NEW.room_id)
        THEN RAISE(ABORT, 'room capacity reached')
    END;
END;

CREATE TRIGGER IF NOT EXISTS trg_allocations_capacity_update
BEFORE UPDATE OF room_id, bed_number, status ON room_allocations
WHEN NEW.status = 'active'
BEGIN
    SELECT CASE
        WHEN NEW.bed_number > (SELECT capacity FROM rooms WHERE id = NEW.room_id)
        THEN RAISE(ABORT, 'bed number exceeds room capacity')
        WHEN (SELECT COUNT(*) FROM room_allocations WHERE room_id = NEW.room_id AND status = 'active' AND id <> OLD.id) >= (SELECT capacity FROM rooms WHERE id = NEW.room_id)
        THEN RAISE(ABORT, 'room capacity reached')
    END;
END;

CREATE TRIGGER IF NOT EXISTS trg_rooms_capacity_update
BEFORE UPDATE OF capacity ON rooms
WHEN NEW.capacity < (SELECT COUNT(*) FROM room_allocations WHERE room_id = OLD.id AND status = 'active')
BEGIN
    SELECT RAISE(ABORT, 'capacity cannot be lower than active allocations');
END;

INSERT OR IGNORE INTO rooms (room_number, room_type, floor, capacity, status) VALUES
    ('A-101', 'Double', 1, 2, 'available'),
    ('A-102', 'Triple', 1, 3, 'available'),
    ('B-201', 'Dormitory', 2, 6, 'available'),
    ('B-202', 'Single', 2, 1, 'available');

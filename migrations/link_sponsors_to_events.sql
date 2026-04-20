-- Add sponsors to existing events
UPDATE events SET sponsor_id = 3 WHERE titre LIKE '%Cardiologie%' LIMIT 1;
UPDATE events SET sponsor_id = 4 WHERE titre LIKE '%Dermatologie%' LIMIT 1;
UPDATE events SET sponsor_id = 1 WHERE titre LIKE '%Esthétique%' LIMIT 1;

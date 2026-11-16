# QuickCare API

Base URL (local): http://127.0.0.1:8000/api

## GET /departments
Returns the list of departments with their hospital name.

Response 200:
```json
{
  "data": [
    {
      "id": 1,
      "name": "General Medicine",
      "description": "First check-up and common complaints. Not sure where to go? Start here.",
      "common_conditions": "fever, cough, cold, dizziness, fatigue",
      "hospital_name": "General Hospital Jakarta"
    }
  ]
}
```

## GET /hospitals
Returns active hospitals. Optional query: `city` (partial match).

Example: /hospitals?city=Jakarta

Response 200:
```json
{
  "data": [
    {
      "id": 1,
      "name": "General Hospital Jakarta",
      "type": "general_hospital",
      "address": "Jl. Contoh No. 1",
      "city": "Jakarta Pusat",
      "image_url": null
    }
  ]
}
```

## GET /doctors
Returns active doctors with their department name. Optional query: `department_id`.

Example: /doctors?department_id=2

Response 200:
```json
{
  "data": [
    {
      "id": 2,
      "name": "Dr. Siti Rahma",
      "specialization": "Dermatologist",
      "photo_url": null,
      "department_name": "Dermatology"
    }
  ]
}
```  
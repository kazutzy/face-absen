Teachable Machine model placeholder

Place your exported Teachable Machine Image model files here:

- model.json
- metadata.json
- weights.bin (or group of shard files depending on export)

Steps to export from Teachable Machine:
1. Train your Image project and add labels matching student identifiers (e.g., siswa_fajar).
2. Click Export -> "Upload my model" (or download the web model) to get the model files.
3. Copy the `model.json`, `metadata.json`, and weight files into this folder.
4. Ensure the application setting `teachable_machine_model_url` points to the folder URL, for example: `http://localhost/projectrpl/public/my_model/`.

After placing the files, open the scanner page and verify the model loads correctly.

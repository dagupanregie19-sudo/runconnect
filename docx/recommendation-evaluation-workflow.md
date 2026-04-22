# Recommendation Evaluation Workflow

This workflow uses `docx/RunConnect1.xlsx` as the offline dataset source for panel-ready recommendation metrics.

## How this maps to your SOP and objectives

| What you claim (SOP / objective) | What in this repo supports it | Commands |
|----------------------------------|--------------------------------|----------|
| Hybrid recommendation + measurable accuracy | `RecommendationEvaluationService` (content + collaborative + history channels) + F1@K | `reco:evaluate`, `reco:grid-search` |
| Dataset / survey evidence | Excel import → `recommendation_training_samples` | `reco:import-excel` |
| Reproducible experiments for Chapter 4 | `recommendation_experiment_runs` (weights + metrics JSON) | same commands |

Code entry points (read class docblocks for full usage):

- `app/Console/Commands/ImportRecommendationDatasetCommand.php` — step 1
- `app/Console/Commands/EvaluateRecommendationModelCommand.php` — step 2
- `app/Console/Commands/GridSearchRecommendationWeightsCommand.php` — step 3 (optional)

## 1) Run migrations

```bash
php artisan migrate
```

## 2) Import Excel data

Default path:

```bash
php artisan reco:import-excel --truncate
```

Custom path:

```bash
php artisan reco:import-excel "C:\path\to\RunConnect1.xlsx" --truncate
```

## 3) Run a single evaluation

```bash
php artisan reco:evaluate --k=3 --weights=0.5,0.3,0.2 --split=time --test-ratio=0.2 --name=baseline
```

- `--weights` format is `content,collab,history`
- Weights are auto-normalized to sum to `1.0`
- `--split=time` is recommended for thesis defense

## 4) Run grid search for best weights

```bash
php artisan reco:grid-search --k=3 --split=time --test-ratio=0.2 --name-prefix=panel
```

## 5) Where results are stored

- Imported samples: `recommendation_training_samples`
- Experiment runs + metrics: `recommendation_experiment_runs`

Metrics stored:

- `precision_at_k`
- `recall_at_k`
- `f1_at_k`
- `hit_rate_at_k`
- `accuracy_at_1`

## 6) Suggested panel reporting

Use:

- One baseline run (`reco:evaluate`)
- One grid-search summary (`reco:grid-search`)
- Final selected best run by `f1_at_k`

Then present:

1. Dataset size (train/test split counts)
2. Chosen K and split method
3. Final weights used
4. Precision@K / Recall@K / F1@K / Accuracy@1


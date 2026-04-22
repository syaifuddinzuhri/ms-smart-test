<?php

namespace App\Filament\Student\Pages\Traits;

use Livewire\Attributes\Computed;

trait HasExamNavigation
{
    public function setTab($tab)
    {
        $lastCurrentQuestionId = $this->currentQuestionId;

        if ($lastCurrentQuestionId) {
            $this->saveAnswer($lastCurrentQuestionId, false);
        }

        if ($tab === 'pg' && $this->totalPG === 0)
            return;
        if ($tab === 'essay' && $this->totalEssay === 0)
            return;

        $this->activeTab = $tab;
        $this->currentStep = 1;

    }

    public function next()
    {
        $lastCurrentQuestionId = $this->currentQuestionId;

        if ($lastCurrentQuestionId) {
            $this->saveAnswer($lastCurrentQuestionId, false);
        }

        if ($this->activeTab === 'pg') {
            // Jika masih ada soal PG selanjutnya
            if ($this->currentStep < $this->totalPG) {
                $this->currentStep++;
            }
            // Jika PG habis, pindah ke Essay (jika ada soal essay)
            elseif ($this->totalEssay > 0) {
                $this->activeTab = 'essay';
                $this->currentStep = 1;
            }
        } else {
            // Navigasi di dalam tab Essay
            if ($this->currentStep < $this->totalEssay) {
                $this->currentStep++;
            }
        }

        $this->dispatch('step-changed');
    }

    public function previous()
    {
        if ($this->activeTab === 'essay') {
            if ($this->currentStep > 1) {
                $this->currentStep--;
            }
            // Jika di Essay nomor 1, kembali ke PG nomor terakhir
            elseif ($this->totalPG > 0) {
                $this->activeTab = 'pg';
                $this->currentStep = $this->totalPG;
            }
        } else {
            // Navigasi di dalam tab PG
            if ($this->currentStep > 1) {
                $this->currentStep--;
            }
        }
        $this->dispatch('step-changed');
    }

    public function goToStep($tab, $step)
    {
        $lastCurrentQuestionId = $this->currentQuestionId;

        if ($lastCurrentQuestionId) {
            $this->saveAnswer($lastCurrentQuestionId, false);
        }
        $this->activeTab = $tab;
        $this->currentStep = $step;
        $this->dispatch('step-changed');
    }

    /**
     * Computed Properties untuk mempermudah pengecekan di Blade
     */
    #[Computed()]
    public function currentQuestion()
    {
        return $this->activeTab === 'pg'
            ? $this->pgQuestions->get($this->currentStep - 1)
            : $this->essayQuestions->get($this->currentStep - 1);
    }

    #[Computed]
    public function currentQuestionId()
    {
        return $this->currentQuestion?->id;
    }

    #[Computed]
    public function isAbsoluteFirst()
    {
        return ($this->activeTab === 'pg' && $this->currentStep === 1) ||
            ($this->totalPG === 0 && $this->activeTab === 'essay' && $this->currentStep === 1);
    }

    #[Computed]
    public function isAbsoluteLast()
    {
        return ($this->activeTab === 'essay' && $this->currentStep === $this->totalEssay) ||
            ($this->totalEssay === 0 && $this->activeTab === 'pg' && $this->currentStep === $this->totalPG);
    }

    public function backToDashboard()
    {
        $this->dispatch('prepare-navigation');

        $this->saveAnswer(); // Simpan progres terakhir

        $this->session->update([
            'token' => null,
            'system_id' => null
        ]);

        return redirect()->to('/input-token?exam_id=' . $this->exam->id);
    }
}

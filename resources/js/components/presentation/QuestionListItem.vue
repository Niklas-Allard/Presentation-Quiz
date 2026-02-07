<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import {
  GripVertical,
  Clock,
  MoreVertical,
  Pencil,
  Trash2,
  HelpCircle
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import QuestionEditorForm from './QuestionEditorForm.vue';
import type { Question } from '@/types/quiz';

const props = defineProps<{
  question: Question;
  index: number;
  groupName: string;
}>();

const showFullEditor = ref(false);

function editQuestion() {
  showFullEditor.value = true;
}

function handleSaveFromDialog() {
  showFullEditor.value = false;
}

function deleteQuestion() {
  if (!confirm('Are you sure you want to delete this question?')) return;
  
  router.delete(`/questions/${props.question.id}`, {
    preserveScroll: true,
  });
}
</script>

<template>
  <div class="group flex items-center gap-3 rounded-md border bg-card p-3 text-card-foreground shadow-sm transition-all hover:shadow-md">
    
    <!-- 
      WICHTIG: Hier ist die Klasse 'drag-handle'.
      Ohne diese Klasse funktioniert das Drag & Drop im Parent nicht,
      da dort handle=".drag-handle" definiert ist.
    -->
    <div class="drag-handle cursor-grab active:cursor-grabbing p-1 text-muted-foreground hover:text-foreground">
      <GripVertical class="h-5 w-5" />
    </div>

    <!-- Index Nummer -->
    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium text-muted-foreground">
      {{ index + 1 }}
    </div>

    <!-- Frage Inhalt -->
    <div class="flex flex-1 flex-col gap-1">
      <div class="flex items-center gap-2">
        <span class="font-medium leading-none">
          {{ question.content.text || 'Untitled Question' }}
        </span>
      </div>
      
      <div class="flex items-center gap-2 text-xs text-muted-foreground">
        <!-- Zeitlimit Badge/Info -->
        <Badge variant="outline" class="flex items-center gap-1 h-5 px-1.5 font-normal">
          <Clock class="h-3 w-3" />
          {{ question.time_limit_seconds }}s
        </Badge>
      </div>
    </div>

    <!-- Aktionen -->
    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button variant="ghost" size="icon" class="h-8 w-8">
          <MoreVertical class="h-4 w-4" />
          <span class="sr-only">Open menu</span>
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuItem @click="editQuestion">
          <Pencil class="mr-2 h-4 w-4" />
          Edit Question
        </DropdownMenuItem>
        <DropdownMenuItem @click="deleteQuestion" class="text-destructive focus:text-destructive">
          <Trash2 class="mr-2 h-4 w-4" />
          Delete Question
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </div>

  <!-- Full Editor Dialog -->
  <Dialog v-model:open="showFullEditor">
    <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Edit Question</DialogTitle>
      </DialogHeader>
      <QuestionEditorForm
        :question="question"
        @save="handleSaveFromDialog"
        @cancel="showFullEditor = false"
      />
    </DialogContent>
  </Dialog>
</template>
